(() => {
    'use strict';

    /**
     * Convert Shefy REST URL:
     *
     * /wp-json/shefy/v1/chat
     *
     * into:
     *
     * /wp-json/wc/store/v1/products/
     *
     * This also preserves sites using:
     * /index.php/wp-json/
     */
    function getProductApiBase() {
        if (
            typeof ShefyConfig === 'undefined' ||
            !ShefyConfig.restUrl
        ) {
            return null;
        }

        return ShefyConfig.restUrl.replace(
            /shefy\/v1\/chat\/?$/,
            'wc/store/v1/products/'
        );
    }


    /**
     * WooCommerce Store API prices are generally returned
     * in minor currency units.
     *
     * Example:
     * price = "900"
     * currency_minor_unit = 2
     *
     * becomes:
     * $9.00
     */
    function formatPrice(product) {
        if (!product.prices) {
            return '';
        }

        const raw = Number(product.prices.price);

        if (!Number.isFinite(raw)) {
            return '';
        }

        const minorUnit = Number.isInteger(
            product.prices.currency_minor_unit
        )
            ? product.prices.currency_minor_unit
            : 2;

        const amount = raw / Math.pow(10, minorUnit);

        try {
            return new Intl.NumberFormat(undefined, {
                style: 'currency',
                currency:
                    product.prices.currency_code || 'USD',
            }).format(amount);
        } catch (error) {
            return (
                (product.prices.currency_symbol || '$') +
                amount.toFixed(minorUnit)
            );
        }
    }


    /**
     * Woo descriptions contain HTML.
     * Convert to plain text before putting them in the card.
     */
    function htmlToText(html) {
        const temp = document.createElement('div');

        temp.innerHTML = html || '';

        return (temp.textContent || '').trim();
    }


    /**
     * Build one WooCommerce product card.
     */
    function buildCard(product) {
        const card = document.createElement('article');

        card.className = 'shefy-product-card';


        /*
         * Image
         */
        if (
            Array.isArray(product.images) &&
            product.images.length
        ) {
            const imageData = product.images[0];

            const image = document.createElement('img');

            image.className = 'shefy-product-card__image';

            image.src =
                imageData.thumbnail ||
                imageData.src;

            image.alt =
                imageData.alt ||
                product.name ||
                '';

            image.loading = 'lazy';

            card.appendChild(image);
        }


        /*
         * Content
         */
        const content = document.createElement('div');

        content.className =
            'shefy-product-card__content';


        /*
         * Name
         */
        const title = document.createElement('div');

        title.className =
            'shefy-product-card__title';

        title.textContent =
            product.name || 'Menu item';

        content.appendChild(title);


        /*
         * Price
         */
        const formattedPrice =
            formatPrice(product);

        if (formattedPrice) {
            const price =
                document.createElement('div');

            price.className =
                'shefy-product-card__price';

            price.textContent =
                formattedPrice;

            content.appendChild(price);
        }


        /*
         * Description
         */
        const descriptionText =
            htmlToText(
                product.short_description ||
                product.description ||
                ''
            );

        if (descriptionText) {
            const description =
                document.createElement('div');

            description.className =
                'shefy-product-card__description';

            description.textContent =
                descriptionText;

            content.appendChild(description);
        }


        /*
         * Button
         *
         * Simple products:
         * normal WooCommerce add-to-cart URL.
         *
         * Variable / option products:
         * send customer to the product page.
         */
        const button =
            document.createElement('a');

        button.className =
            'shefy-product-card__button';

if (
    !product.has_options &&
    product.is_purchasable &&
    product.is_in_stock
) {
            /*
             * Woo understands:
             *
             * ?add-to-cart=123
             *
             * This deliberately avoids us building our own
             * cart API in v1.
             */
            const url =
                new URL(
                    window.location.href
                );

            url.searchParams.set(
                'add-to-cart',
                product.id
            );

            button.href = url.toString();

            button.textContent =
                'Add to order';

        } else {
            button.href =
                product.permalink || '#';

            button.textContent =
                product.has_options
                    ? 'Choose options'
                    : 'View item';
        }

        content.appendChild(button);

        card.appendChild(content);

        return card;
    }


    /**
     * Get one product from WooCommerce.
     */
    async function getProduct(productId) {
        const base = getProductApiBase();

        if (!base) {
            throw new Error(
                'WooCommerce product API unavailable.'
            );
        }

        const response = await fetch(
            base +
            encodeURIComponent(productId),
            {
                headers: {
                    Accept: 'application/json',
                },
            }
        );

        if (!response.ok) {
            throw new Error(
                `Could not load product ${productId}.`
            );
        }

        return response.json();
    }


    /**
     * Public method:
     *
     * ShefyProducts.render(
     *     [27, 46, 29],
     *     someContainer
     * );
     */
    async function render(productIds, container) {
        if (
            !container ||
            !Array.isArray(productIds)
        ) {
            return;
        }

        /*
         * Sanitize and limit again on the frontend.
         *
         * PHP/model already limit this,
         * but there is no reason not to be defensive.
         */
        const ids = [
            ...new Set(
                productIds
                    .map(id =>
                        Number.parseInt(id, 10)
                    )
                    .filter(id =>
                        Number.isInteger(id) &&
                        id > 0
                    )
            ),
        ].slice(0, 3);

        if (!ids.length) {
            return;
        }

        const wrapper =
            document.createElement('div');

        wrapper.className =
            'shefy-product-cards';

        wrapper.setAttribute(
            'aria-label',
            'Suggested menu items'
        );

        container.appendChild(wrapper);


        /*
         * Fetch all recommended products together.
         */
        const results =
            await Promise.allSettled(
                ids.map(getProduct)
            );

        results.forEach(result => {
            if (
                result.status ===
                'fulfilled'
            ) {
                wrapper.appendChild(
                    buildCard(result.value)
                );
            }
        });


        /*
         * If Woo couldn't resolve any of them,
         * don't leave an empty box behind.
         */
        if (!wrapper.children.length) {
            wrapper.remove();
        }
    }


    /*
     * Expose one tiny API to shefy.js.
     */
    window.ShefyProducts = {
        render,
    };

})();
