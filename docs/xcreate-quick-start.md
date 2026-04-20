# Xcreate Quick Start

This is the shortest practical setup path for `xcreate`.

## 1. Install

1. Install the module in XOOPS.
2. Open module preferences.
3. Set:
   - items per page
   - whether frontend submission is allowed
   - max upload size
   - allowed extensions

## 2. Build one content type first

Do not create many categories on day one. Build one complete example first.

Recommended sample:

- Category: `Products`
- Fields:
  - price (`number`)
  - brand (`text`)
  - gallery (`gallery`)
  - spec sheet (`file`)
  - featured (`radio`)

## 3. Admin flow

1. Create category in [admin/categories.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/categories.php:1)
2. Create fields in [admin/fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/fields.php:1)
3. Optionally create groups in [admin/groups.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/groups.php:1)
4. Add item in [admin/items.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/items.php:1)

## 4. Public flow

Check these pages:

1. list page: [index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)
2. item page: [item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)
3. search page: [search.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/search.php:1)
4. submit page: [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)

## 5. Templates

Core templates:

- [templates/xcreate_index.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_index.tpl:1)
- [templates/xcreate_item.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_item.tpl:1)
- [templates/xcreate_submit.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_submit.tpl:1)
- [templates/xcreate_search.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_search.tpl:1)

If one category needs a custom layout, assign:

- `cat_template` for item detail
- `cat_list_template` for category list

## 6. Blocks

Useful starting blocks:

- recent items block
- filter block

Files:

- [blocks/xcreate_blocks.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_blocks.php:1)
- [blocks/xcreate_filter_block.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_filter_block.php:1)

## 7. SEO starter rules

For each category and item:

1. keep slug short
2. keep title readable
3. add meta description where useful
4. add OG image on important pages

## 8. Avoid these mistakes

- creating items before fields
- changing internal field names repeatedly
- making every field repeatable
- assuming comments are built in
- importing big data before testing a small sample

## 9. If something looks wrong

Check:

- category assignment
- field status
- field order
- template assignment
- upload path `/uploads/xcreate/`
- module update after template metadata changes

