(() => {
    const root = document.querySelector('[data-shefy]');
    if (!root || typeof ShefyConfig === 'undefined') {
        return;
    }

    const form = root.querySelector('[data-shefy-form]');
    const input = root.querySelector('[data-shefy-input]');
    const messages = root.querySelector('[data-shefy-messages]');

const appendMessage = (text, role) => {
    const el = document.createElement('div');

    el.className = `shefy__message shefy__message--${role}`;
    el.textContent = text;

    messages.appendChild(el);
    messages.scrollTop = messages.scrollHeight;

    return el;
};

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const message = input.value.trim();
        if (!message) {
            return;
        }

        appendMessage(message, 'user');
        input.value = '';
        input.disabled = true;

        try {
            const response = await fetch(ShefyConfig.restUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': ShefyConfig.nonce,
                },
                body: JSON.stringify({ message }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || 'Shefy request failed.');
            }


const reply = appendMessage(
    data.reply || 'No response.',
    'assistant'
);

if (
    window.ShefyProducts &&
    Array.isArray(data.product_ids) &&
    data.product_ids.length
) {
    await ShefyProducts.render(
        data.product_ids,
        reply.parentElement
    );
}

        } catch (error) {
            appendMessage(error.message || 'Something went wrong.', 'error');
        } finally {
            input.disabled = false;
            input.focus();
        }
    });
})();
