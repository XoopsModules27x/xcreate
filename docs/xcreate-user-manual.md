# Xcreate User Manual

This manual is for site builders, content editors, and administrators using the `xcreate` module in XOOPS.

## Table of contents

1. What Xcreate does
2. Setup flow
3. Admin pages
4. Public pages
5. Field types
6. Repeatable fields
7. Gallery and uploads
8. Blocks
9. SEO
10. Ratings and comments
11. Import and export tips
12. Common mistakes
13. Recommended starter checklist

## 1. What Xcreate does

`xcreate` lets you build custom content types without creating a separate module for each one. You define:

1. categories
2. fields for each category
3. optional field groups
4. content items inside those categories
5. templates for how each category and item should look

Typical uses:

- product catalogs
- listings and directories
- job posts
- movie or game databases
- project portfolios
- event directories

## 2. Setup flow

Follow this order the first time you configure the module.

### Step 1: Install and enable

1. Install the module from XOOPS admin.
2. Confirm the module appears in the admin menu.
3. Review preferences such as:
   - items per page
   - user submission
   - max upload size
   - allowed extensions

### Step 2: Create categories

Open [admin/categories.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/categories.php:1).

For each category, set:

- name
- parent category if needed
- description
- image
- order
- optional item template
- optional list template
- SEO values

### Step 3: Add fields

Open [admin/fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/fields.php:1).

Attach fields to the correct category. Decide:

- label shown to users
- internal field name
- field type
- required or optional
- repeatable or not
- default value
- order
- active or inactive

### Step 4: Add optional field groups

Open [admin/groups.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/groups.php:1).

Use groups when a category has many fields and you want the form organized into sections or tabs.

### Step 5: Add items

Use one of these:

- [admin/items.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/items.php:1) for admin entry
- [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1) for frontend entry

## 3. Admin pages

### Dashboard

[admin/index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/index.php:1)

Use it for:

- overview stats
- shortcuts into categories, fields, items, import, and export

### Categories

[admin/categories.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/categories.php:1)

Use it to:

- create and edit categories
- build hierarchy
- assign templates
- configure category SEO

### Fields

[admin/fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/fields.php:1)

Use it to:

- define category-specific fields
- add options for select/radio/checkbox
- enable repeatable fields
- add field dependency rules
- enable lookup selectors

### Groups

[admin/groups.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/groups.php:1)

Use it to:

- group fields into named sections
- make long forms easier to manage

### Items

[admin/items.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/items.php:1)

Use it to:

- create items
- edit existing items
- approve pending items
- filter and review item lists
- manage item SEO

### Import and export

- [admin/import.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/import.php:1)
- [admin/export.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/export.php:1)

Use these for bulk content movement.

## 4. Public pages

### Category and item list page

[index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)

This page can show:

- recent content across the module
- one category’s content
- filtered results using field-based filters

### Item detail page

[item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)

This page shows:

- item title and description
- dynamic field values
- SEO and sharing data
- rating output

### Search page

[search.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/search.php:1)

Use it for:

- keyword search
- category filtering
- dynamic field filtering
- date filtering

### Submission page

[submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)

Use it for frontend item creation or editing when allowed by site configuration.

## 5. Field types

Current field types include:

- text
- textarea
- editor
- image
- gallery
- file
- select
- checkbox
- radio
- date
- datetime
- number
- email
- url
- color

## 6. Repeatable fields

Repeatable fields allow more than one value for the same field.

Examples:

- multiple phone numbers
- several links
- more than one file
- multiple gallery groups

Use repeatable fields only when they are truly needed, because they make templates and imports more complex.

## 7. Gallery and uploads

Uploads are stored under `/uploads/xcreate/`.

There are three main upload-related field modes:

- `image`: one image
- `file`: one downloadable file
- `gallery`: multiple images

Best practice:

1. keep filenames and source files clean
2. upload web-safe image formats
3. avoid very large files unless the module preference allows them

## 8. Blocks

The module includes:

- a recent items block
- a filter block
- a larger widget set for homepage/sidebar use

Useful files:

- [blocks/xcreate_blocks.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_blocks.php:1)
- [blocks/xcreate_filter_block.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_filter_block.php:1)
- [blocks/xcreate_widgets.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_widgets.php:1)

Common uses:

- recent content sidebar
- faceted filter box on listing pages
- featured content homepage block

## 9. SEO

The module supports:

- SEO slugs for categories and items
- meta title
- meta description
- meta keywords
- OG image
- canonical URL
- noindex flag

Recommended workflow:

1. let the slug auto-generate first
2. keep titles short and descriptive
3. use plain-language meta descriptions
4. set an OG image for high-value pages

## 10. Ratings and comments

### Ratings

Ratings are built in. Item pages can show star-based rating data.

### Comments

Comments are not currently implemented as a real built-in feature in this codebase. Do not plan your workflow around XOOPS comments support here unless you are adding custom development.

## 11. Import and export tips

### Export

Use export when you want:

- CSV for spreadsheet editing
- JSON for structured migrations

### Import

Use import when you want:

- bulk content creation
- bulk updates

Safe workflow:

1. export a sample first
2. edit that file
3. test with a few rows
4. then run the full import

## 12. Common mistakes

### Creating items before fields

Define the category structure first. Otherwise you will create incomplete content.

### Using unstable field names

Keep field names machine-friendly and stable. Change labels, not internal names.

### Making too many fields repeatable

Repeatable fields are powerful but harder to manage.

### Forgetting module update after template changes

If you add or rename template files, run a module update so XOOPS refreshes metadata.

### Expecting comments to work out of the box

The module currently supports ratings, not a full comment system.

## 13. Recommended starter checklist

1. configure preferences
2. create one category
3. define a small field set
4. create one test item
5. review list page, item page, and search page
6. add a recent block and a filter block
7. only then scale to more categories
