# Shefy

Shefy is a lightweight AI waiter for WordPress and WooCommerce. FULLY LLM GENERATED, (exceot these words here, they were writen by meat tenticles).

It uses the restaurant's WooCommerce menu as structured context, sends customer questions to an LLM, and returns conversational recommendations plus WooCommerce product IDs that can be rendered as product cards.

## Current Features

- WooCommerce menu snapshot
- 100-item full-context limit
- Tonight's notes for temporary specials or availability
- AI-powered menu Q&A and recommendations
- Structured responses with product IDs
- WooCommerce product card rendering
- Shortcode-based frontend chat interface

## Basic Flow

1. WooCommerce products are used as the menu source.
2. Shefy rebuilds a normalized menu snapshot.
3. The customer asks a question.
4. The menu snapshot and tonight's notes are sent to the AI.
5. The AI returns:
   - a customer-facing reply
   - matching WooCommerce product IDs
6. The frontend renders those products as WooCommerce cards.

## Requirements

- WordPress
- WooCommerce
- OpenAI API key

## API Key

Add your OpenAI API key to `wp-config.php`:

```php
define('SHEFY_OPENAI_API_KEY', 'sk-...');
```

## Usage

Activate the plugin, rebuild the menu from the Shefy admin page, then add the shortcode to a page:

```text
[shefy_waiter]
```

## Status

Early development / proof of concept.

The current version is intentionally simple and is designed for menus with up to 100 active items.
