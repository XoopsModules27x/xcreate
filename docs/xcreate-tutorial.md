# Xcreate Module Tutorial

This document explains how to use `xcreate` as a site builder and how the module is put together as a developer. It is written against the current codebase in `modules/xcreate`.

## Table of contents

1. Part 1: User Guide
2. What Xcreate is for
3. Main benefits
4. Core concepts
5. Installation and first setup flow
6. Admin pages and what they do
7. Public pages and content flow
8. Field types and when to use them
9. Repeatable fields
10. Conditional fields
11. Database lookup / selector fields
12. Gallery and uploads
13. Templates and view customization
14. Blocks and widgets
15. SEO support
16. Ratings and comments
17. Import and export workflow
18. Common setup patterns
19. Common user mistakes
20. Recommended day-one checklist
21. Part 2: Developer Guide
22. Module architecture at a glance
23. Schema overview
24. Objects and handlers
25. Helper split and why it matters
26. Public controllers
27. Admin controllers
28. AJAX endpoints
29. Template shaping contract
30. Upload architecture
31. Lifecycle hooks and preload behavior
32. Blocks as a second presentation layer
33. How to build something similar
34. What this module already gets right
35. What a developer should be careful about
36. Suggested reading order in the codebase
37. Final practical advice

## Part 1: User Guide

### 1. What Xcreate is for

`xcreate` is a category-driven content builder for XOOPS. Instead of hardcoding one content type, it lets you define:

1. Categories
2. Custom fields per category
3. Optional field groups
4. Item records that store common fields plus dynamic field values
5. Category-specific list and detail templates

That makes it useful for:

- product catalogs
- real-estate listings
- job boards
- directories
- portfolios
- event listings
- game or movie databases

### 2. Main benefits

- One module can support many content structures.
- Each category can have its own field model.
- Repeatable fields let one item store multiple values.
- File, image, and gallery fields are built in.
- SEO slug and meta fields exist for categories and items.
- Search and filter pages work across dynamic fields.
- Blocks can expose recent items and filter widgets anywhere in the site.

### 3. Core concepts

Before setup, keep these terms straight:

- `Category`: the content bucket, such as “Products” or “Apartments”.
- `Field`: a custom input attached to one category, such as price, brand, or gallery.
- `Field option`: a value/label pair for `select`, `radio`, or `checkbox`.
- `Field group`: an optional visual grouping of fields into tabs or sections.
- `Item`: one content entry in a category.
- `Template`: the Smarty view used to render category lists or item details.

### 4. Installation and first setup flow

#### Step 1: Install the module

1. Copy `xcreate` into `modules/xcreate`.
2. Install it from XOOPS module administration.
3. Verify that the SQL tables are created from [sql/mysql.sql](/C:/wamp64/www/270test3/htdocs/modules/xcreate/sql/mysql.sql:1).

#### Step 2: Configure the module

In module preferences, review:

- `items_per_page`
- `allow_user_submit`
- `upload_maxsize`
- `upload_allowed_ext`

These are registered in [xoops_version.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/xoops_version.php:89).

#### Step 3: Create categories

Use [admin/categories.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/categories.php:1) to define:

- category name
- parent category
- description
- image
- weight
- detail template
- list template
- SEO fields

Create your top-level categories first. If you need hierarchy, add child categories afterward.

#### Step 4: Add custom fields

Use [admin/fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/fields.php:1) to attach fields to each category.

For each field, decide:

- internal field name
- label shown to users
- field type
- required or optional
- repeatable or single-value
- default value
- validation rule
- display order
- active or inactive
- conditional logic
- lookup behavior

#### Step 5: Add optional field groups

Use [admin/groups.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/groups.php:1) if you want to group fields into tabs or named sections. This is useful when a category has many fields and the form would otherwise become too long.

#### Step 6: Add content items

Use either:

- [admin/items.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/items.php:1) for admin-managed content
- [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1) for frontend submission/editing

At this point the module is usable.

### 5. Admin pages and what they do

#### Dashboard

[admin/index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/index.php:1)

Use it for:

- high-level module stats
- shortcuts into categories, fields, and items
- overview of current content volume

#### Categories

[admin/categories.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/categories.php:1)

Use it for:

- creating category trees
- setting category descriptions and images
- assigning per-category templates
- managing category SEO

#### Fields

[admin/fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/fields.php:1)

Use it for:

- defining the schema of each category
- choosing field types
- setting repeatable behavior
- setting conditional display rules
- enabling database lookup / selector behavior

#### Groups

[admin/groups.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/groups.php:1)

Use it for:

- grouping fields into a friendlier editing layout
- assigning labels, colors, and icons to groups

#### Items

[admin/items.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/items.php:1)

Use it for:

- creating and editing items
- approving pending items
- filtering the item list
- editing SEO values per item

#### Import and export

- [admin/import.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/import.php:1)
- [admin/export.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/export.php:1)

Use them when:

- migrating content in bulk
- backing up content with field values
- loading items from CSV or JSON

#### Debug log

[admin/debug_log.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/debug_log.php:1)

Use it only for troubleshooting, not for normal content operations.

### 6. Public pages and content flow

#### Category and item listing

[index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)

This page does three jobs:

- site-level recent content when no category is chosen
- category list view when `cat_id` or `cat_slug` is selected
- filtered list view when `xcf_*` filter parameters are present

It supports SEO routes such as:

- `/modules/xcreate/category-slug/`

#### Item detail page

[item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)

This renders a single item and supports:

- legacy `?id=...`
- SEO item routes using category and item slugs
- hits counting
- dynamic field rendering
- rating display
- SEO payload assignment

#### Search page

[search.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/search.php:1)

This is the advanced search page. It can search:

- item title
- item description
- dynamic field values

It also supports:

- category filter
- field filter
- date range
- multiple sort modes

#### Submission page

[submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)

This is the frontend form for:

- creating items
- editing owned items
- uploading files and images
- processing repeatable values
- evaluating field conditions

### 7. Field types and when to use them

Defined in [class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:12).

- `text`: short identifiers, names, phone numbers
- `textarea`: plain multi-line text
- `editor`: rich HTML content
- `image`: one image file
- `gallery`: multiple image files stored as one gallery value set
- `file`: one document or downloadable file
- `select`: single value from a dropdown
- `checkbox`: multiple values
- `radio`: single choice with always-visible options
- `date`: date only
- `datetime`: date and time
- `number`: prices, counts, sizes, ratings
- `email`: email address
- `url`: external link
- `color`: simple visual color value

### 8. Repeatable fields

Repeatable fields allow multiple entries under the same logical field, such as:

- several phone numbers
- several contact emails
- multiple attachments
- several related links

In practice:

1. Mark the field as repeatable in the field editor.
2. The form shows an add button for extra instances.
3. Values are stored in `xcreate_field_values` with a `value_index`.

This works for both text-like values and uploads. Gallery fields are a special case because they may already contain multiple files within one logical field instance.

### 9. Conditional fields

The module supports field dependency rules. A field can be shown only when another field matches a condition.

Examples:

- show “Rent period” only when listing type is “Rental”
- show “Warranty months” only when item is “New”

The rule is stored in `field_condition` as JSON and evaluated in:

- frontend submission
- admin item editing
- the JS condition engine in [class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:55)

### 10. Database lookup / selector fields

The field editor supports a lookup mode where an admin can search content from another category and transfer either:

- the target item title
- or a selected field value

This is useful for manual relations such as:

- selecting a manufacturer
- selecting a game studio
- selecting a city or branch item from another category

The lookup endpoints live under:

- [ajax/lookup.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/lookup.php:1)
- [ajax/get_cat_fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/get_cat_fields.php:1)

### 11. Gallery and uploads

Uploads are stored under:

- `/uploads/xcreate/`

From a user perspective:

- `image` stores one image
- `file` stores one document
- `gallery` stores multiple images

Frontend upload handling is in [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:49). Rendering support also exists in [class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:167) and [class/fields_helper.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/fields_helper.php:49).

### 12. Templates and view customization

The core templates are:

- [templates/xcreate_index.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_index.tpl:1)
- [templates/xcreate_item.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_item.tpl:1)
- [templates/xcreate_submit.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_submit.tpl:1)
- [templates/xcreate_search.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_search.tpl:1)

Per-category customization:

- `cat_template` controls the detail template
- `cat_list_template` controls the list template

The module also scans `templates/*.tpl` in [xoops_version.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/xoops_version.php:57), so custom templates can become selectable after module update.

Template data is shaped by:

- controller arrays in `index.php` and `item.php`
- `XcreateFieldsHelper::assignItemFields()`
- `XcreateFieldsHelper::appendFieldsToList()`

### 13. Blocks and widgets

#### Recent block

[blocks/xcreate_blocks.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_blocks.php:1)

It can show:

- recent items
- optionally restricted to one category

#### Filter block

[blocks/xcreate_filter_block.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_filter_block.php:1)

It can build filter interfaces based on selected field definitions and then drive the list page using `xcf_*` parameters.

#### Widget pack

[blocks/xcreate_widgets.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_widgets.php:1)

The codebase contains ten widget-style block functions such as:

- recent
- popular
- top rated
- random
- featured
- grouped by category
- stats
- slider
- tag cloud
- activity

Use these if you want `xcreate` content distributed across sidebars or homepage sections.

### 14. SEO support

The module includes:

- item slug
- category slug
- item meta title / description / keywords
- category meta title / description / keywords
- OG image
- canonical URL
- noindex flag

Slug logic is in [class/slug.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/slug.php:1). SEO values are assigned in:

- [index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:96)
- [item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:46)

Recommended user workflow:

1. Let the slug auto-generate first.
2. Fill meta title only when the visible item title is too long.
3. Fill meta description with a short plain-language summary.
4. Use OG image for social sharing when the main content image is not ideal.
5. Use `noindex` only for utility or duplicate pages.

### 15. Ratings and comments

#### Ratings

Ratings are implemented.

- storage: `xcreate_ratings`
- handler: [class/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/rating.php:1)
- AJAX endpoint: [ajax/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/rating.php:1)

#### Comments

Comments are not currently implemented as a first-class XOOPS comments integration in this codebase. The module includes example or presentation templates that visually show comments, but there is no active comment controller, comment schema, or `hasComments` registration in [xoops_version.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/xoops_version.php:1).

That means users should treat comments as:

- not available out of the box
- something that would need custom development

### 16. Import and export workflow

#### Export

Use [admin/export.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/export.php:1) when you want:

- CSV for spreadsheet work
- JSON for programmatic migration

#### Import

Use [admin/import.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/admin/import.php:1) when you want:

- bulk item creation
- item updates via matching keys
- field population using `field_FIELD_NAME` style columns

Good practice:

1. Export a sample set first.
2. Use that exported file as the import template.
3. Test with a few rows before full import.

### 17. Common setup patterns

#### Pattern A: Product catalog

- categories: Brands or product families
- fields: price, SKU, stock, color, gallery, spec sheet
- blocks: recent, featured, filter

#### Pattern B: Listing directory

- categories: city, district, listing type
- fields: price, area, rooms, map URL, gallery
- search: category + field + date filter

#### Pattern C: Review database

- categories: games, films, books
- fields: release date, platform, genre, trailer URL, gallery
- ratings: enabled on item page

### 18. Common user mistakes

#### Mistake 1: creating items before fields

If you create items before defining category fields, you will have the base title and description only. Always define the category model first.

#### Mistake 2: reusing the same field name badly

`field_name` should be stable and machine-friendly. Do not use changing labels or decorative text as field names.

#### Mistake 3: making everything repeatable

Repeatable fields are useful, but they complicate templates, imports, and edit forms. Use them only where multiple values are genuinely needed.

#### Mistake 4: mixing display intent and storage intent

Use:

- `field_name` for stable internal structure
- `field_label` for translated, user-facing labels

#### Mistake 5: ignoring template assignment

If a category needs a specialized layout, assign custom list/detail templates early instead of piling all logic into the default templates.

#### Mistake 6: treating comments as built in

The current module ships ratings, not a real comment engine.

#### Mistake 7: importing without testing category matches

Import depends on category mapping and field naming. Always test a small batch first.

#### Mistake 8: forgetting module update after template or metadata changes

When adding template files or changing metadata in `xoops_version.php`, run a module update so XOOPS refreshes the registry.

### 19. Recommended day-one checklist

1. Install the module.
2. Set preferences.
3. Create top-level categories.
4. Add fields for one category only.
5. Add one sample item.
6. Confirm list page, item page, and search page behavior.
7. Add SEO values.
8. Add blocks to a theme position.
9. Only then scale to more categories.

## Part 2: Developer Guide

### 1. Module architecture at a glance

`xcreate` is a classic XOOPS module built around:

- `XoopsObject` entity classes
- `XoopsPersistableObjectHandler` handlers for core tables
- procedural public and admin controllers
- Smarty templates
- block functions
- AJAX endpoints

The code is organized under:

- `class/` for entities, handlers, and helpers
- `admin/` for admin controllers
- module root for public controllers
- `ajax/` for JSON-like endpoints
- `blocks/` for XOOPS blocks
- `templates/` for Smarty rendering
- `language/` for translations
- `sql/` for install and update scripts

### 2. Schema overview

The install schema is in [sql/mysql.sql](/C:/wamp64/www/270test3/htdocs/modules/xcreate/sql/mysql.sql:1).

#### `xcreate_categories`

Purpose:

- category tree
- category display settings
- category SEO values

Key columns:

- `cat_id`
- `cat_pid`
- `cat_name`
- `cat_slug`
- `cat_description`
- `cat_image`
- `cat_template`
- `cat_list_template`
- `cat_weight`

#### `xcreate_items`

Purpose:

- core content record

Key columns:

- `item_id`
- `item_cat_id`
- `item_title`
- `item_slug`
- `item_description`
- `item_uid`
- `item_created`
- `item_updated`
- `item_published`
- `item_status`
- `item_hits`

#### `xcreate_fields`

Purpose:

- field definitions per category

Key columns:

- `field_id`
- `field_cat_id`
- `field_name`
- `field_label`
- `field_type`
- `field_required`
- `field_repeatable`
- `field_default_value`
- `field_validation`
- `field_weight`
- `field_status`

The runtime code also expects later columns such as:

- `field_condition`
- `field_lookup_enabled`
- `field_lookup_cat_id`
- `field_lookup_field_id`
- `field_group_id`

Those are introduced through update scripts or runtime table adjustment code, not the base install SQL alone.

#### `xcreate_field_options`

Purpose:

- option storage for `select`, `radio`, and `checkbox`

Key columns:

- `option_field_id`
- `option_value`
- `option_label`
- `option_weight`

#### `xcreate_field_values`

Purpose:

- actual dynamic data for items

Key columns:

- `value_item_id`
- `value_field_id`
- `value_index`
- `value_text`
- `value_file`

This table is the central bridge between items and arbitrary category fields.

#### `xcreate_ratings`

Purpose:

- star voting records

Key columns:

- `rating_item_id`
- `rating_uid`
- `rating_ip`
- `rating_score`

### 3. Objects and handlers

#### Category

[class/category.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/category.php:1)

- `XcreateCategory` defines the category object
- `XcreateCategoryHandler` provides:
  - `getBySlug()`
  - `generateSlug()`
  - `getTree()`
  - `getParentPath()`
  - `hasChildren()`
  - `delete()`

#### Item

[class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:1)

- `XcreateItem` defines the core item object
- `XcreateItemHandler` provides:
  - `getItemsByCategory()`
  - `getRecentItems()`
  - `getBySlug()`
  - `generateSlug()`
  - `updateHits()`
  - `getFieldValues()`
  - `saveFieldValues()`
  - `handleFileUpload()`

#### Field

[class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:1)

- `XcreateField` defines field metadata
- `XcreateFieldHandler` provides:
  - `getFieldsByCategory()`
  - `getConditionsForCategory()`
  - `renderField()`
  - per-type render helpers
  - option loading and saving behavior

#### Group

[class/group.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/group.php:1)

- `XcreateGroup` defines field groups
- `XcreateGroupHandler` provides:
  - `ensureTable()`
  - `getGroupsByCategory()`
  - `getGroupsWithFields()`
  - `deleteGroup()`
  - `getGroupsForSelect()`

#### Rating

[class/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/rating.php:1)

`XcreateRatingHandler` is not a `XoopsPersistableObjectHandler`; it is a custom service-like handler that:

- ensures the table exists
- returns aggregated rating stats
- checks previous vote state
- saves votes

#### Slug

[class/slug.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/slug.php:1)

This class is responsible for:

- transliteration-friendly slug creation
- unique slug collision handling
- item and category URL construction helpers

### 4. Helper split and why it matters

The main helper split is:

- `XcreateFieldHandler`: builds forms and field definitions
- `XcreateFieldsHelper`: shapes stored values for template use

[class/fields_helper.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/fields_helper.php:1) is the presentation-oriented helper. It exists to:

- convert stored field values into template-friendly arrays
- build both raw and display-ready values
- assign shortcut aliases like `f_name` and `fd_name`
- enrich list arrays with field-derived data

This split is important because field definition and field display are different concerns.

### 5. Public controllers

#### `index.php`

Responsibilities:

- resolve category by slug or id
- collect category list
- read active `xcf_*` filters
- run filtered or unfiltered item queries
- build pagination
- assign category and item arrays to Smarty
- assign SEO meta for category context

#### `item.php`

Responsibilities:

- resolve item by slug or id
- choose the category-specific detail template
- assign item-level SEO values
- update item hits
- build breadcrumbs
- assign dynamic fields
- assign rating data

#### `submit.php`

Responsibilities:

- build frontend item forms
- validate required fields
- process conditional-field visibility rules
- handle repeatable values
- handle upload processing
- save item and field values

### 6. Admin controllers

The admin layer is still procedural and page-oriented.

#### `admin/categories.php`

Handles:

- category CRUD
- category image upload
- template creation bootstrap
- category SEO storage

#### `admin/fields.php`

Handles:

- field CRUD
- option CRUD
- conditional rule definition
- lookup configuration

#### `admin/items.php`

Handles:

- admin item CRUD
- approval workflow
- dynamic form rendering
- item SEO storage

#### `admin/groups.php`

Handles:

- field group CRUD
- assigning fields to groups

#### `admin/import.php` and `admin/export.php`

Handle:

- bulk data movement
- field-aware serialization and deserialization

### 7. AJAX endpoints

The AJAX layer lives under `ajax/`.

- [ajax/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/rating.php:1): submit and return rating state
- [ajax/lookup.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/lookup.php:1): lookup-selector search
- [ajax/get_cat_fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/get_cat_fields.php:1): return fields for a category
- [ajax/search_suggest.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/search_suggest.php:1): search suggestion support

When building something similar, these endpoints are where you separate interactive UI behavior from the full page controllers.

### 8. Template shaping contract

The module uses Smarty templates with XOOPS delimiters. The current shaping model is:

- controllers build arrays like `$item_list` or `$category_list`
- `XcreateFieldsHelper` injects dynamic field aliases
- templates consume both high-level arrays and per-field keys

Examples:

- item detail template expects `item`, `category`, `breadcrumb`, `field`, and `custom_fields`
- list templates expect `items`, `categories`, and optional `current_category`
- search template expects `xcreate_search`

This is flexible but loose. If you build something similar, establish a stable view-model contract early so list pages, detail pages, blocks, and search all receive consistent shapes.

### 9. Upload architecture

Uploads currently exist in multiple places:

- [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:49)
- [class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:229)
- admin controllers for category and item forms

Current behavior:

- images and files are stored under `/uploads/xcreate/`
- image uploads may be resized
- gallery fields store multiple filenames
- field values may use `value_file` rather than `value_text`

If you build something similar, centralize uploads into one service early. Right now the module repeats file logic in several code paths.

### 10. Lifecycle hooks and preload behavior

The active lifecycle-like integration currently present is:

- [preload/preload.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/preload/preload.php:1)

What it does:

- registers the module’s Smarty plugin directory during `eventCoreHeaderStart`

Important note:

- There is no proper install/update callback set such as `xoops_module_install_xcreate()` or `xoops_module_update_xcreate()` in an `include/oninstall.inc.php` or `include/onupdate.inc.php` file.
- Some schema evolution currently happens through SQL update files and some through runtime `ensureTable()` or `ALTER TABLE` checks.

So when documenting lifecycle in the current codebase, the honest summary is:

- preload exists
- install SQL exists
- update SQL files exist
- formal lifecycle callback hooks are largely absent

### 11. Blocks as a second presentation layer

The block system is a parallel delivery channel for the same content model.

This is important architecturally because blocks reuse:

- `XcreateItemHandler`
- `XcreateCategoryHandler`
- `XcreateFieldsHelper`

That means if you build a similar module, you should think in three presentation layers from the start:

1. full page list/detail
2. search/filter page
3. reusable blocks/widgets

### 12. How to build something similar

If you wanted to build a cleaner modern version of `xcreate`, the recommended sequence would be:

#### Step 1: lock the content model

Define:

- category table
- item table
- field definition table
- field value table

Do not start with templates first.

#### Step 2: define stable handlers or repositories

Create services for:

- categories
- items
- fields
- field values
- uploads
- slugs

#### Step 3: separate three concerns

Keep these layers separate:

- schema definition
- persistence/query layer
- presentation/view-model layer

`XcreateFieldsHelper` is the beginning of that split. In a newer build, make it explicit.

#### Step 4: create one source of truth for field rendering

A dynamic-content module becomes hard to maintain when every controller renders field HTML differently. Build:

- one field-definition model
- one field-form renderer
- one field-display renderer

#### Step 5: choose a view-model contract

Every template should receive normalized arrays. Avoid ad hoc controller-specific shapes where possible.

#### Step 6: centralize uploads and validation

This should include:

- file naming
- MIME and extension validation
- image resizing
- gallery handling
- path generation
- deletion or replacement rules

#### Step 7: formalize install and update hooks

Use:

- install SQL
- explicit update callbacks
- versioned migration scripts

Do not rely on runtime table alteration in hot request paths.

#### Step 8: keep blocks and AJAX thin

Blocks and AJAX endpoints should reuse the same service layer rather than re-implementing business rules.

### 13. What this module already gets right

- flexible category-to-field modeling
- slug support for both categories and items
- per-category template assignment
- field grouping concept
- repeatable field storage model
- helper-based template enrichment
- reusable block/widget strategy

### 14. What a developer should be careful about

When extending the current module, pay close attention to:

- runtime schema mutation
- duplicate field/render logic across controllers
- upload handling duplication
- inconsistent escaping contracts between controller and template
- search and filter query complexity
- legacy procedural page controllers growing too large

### 15. Suggested reading order in the codebase

If you are new to this module, read in this order:

1. [xoops_version.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/xoops_version.php:1)
2. [sql/mysql.sql](/C:/wamp64/www/270test3/htdocs/modules/xcreate/sql/mysql.sql:1)
3. [class/category.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/category.php:1)
4. [class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:1)
5. [class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:1)
6. [class/fields_helper.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/fields_helper.php:1)
7. [index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)
8. [item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)
9. [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)
10. the `admin/` controllers
11. the `blocks/` controllers
12. the templates

### 16. Final practical advice

For users:

- model one category well before creating many
- keep field names stable
- treat templates as part of the content model, not decoration only

For developers:

- preserve backward compatibility where possible
- keep XOOPS language constants complete
- prefer shared services over copying logic across controllers
- document every new field behavior in both the admin UI and the templates
