# Xcreate Developer Architecture Note

This document is a developer-focused architecture guide for the current `xcreate` module implementation.

## Table of contents

1. Architectural overview
2. Schema model
3. Entity and handler layer
4. Helper split
5. Public controller model
6. Admin controller model
7. Template shaping
8. Upload architecture
9. AJAX and interactivity layer
10. Blocks as presentation adapters
11. Module registration and metadata
12. Preload and lifecycle behavior
13. Dynamic content model in practice
14. How to build something similar
15. What to preserve if you refactor
16. What to improve if you modernize

## 1. Architectural overview

`xcreate` is a classic XOOPS content module built from:

- `XoopsObject` entities
- `XoopsPersistableObjectHandler` persistence handlers
- procedural public controllers
- procedural admin controllers
- Smarty templates
- block functions
- AJAX endpoints
- a preload that registers Smarty plugins

Primary directories:

- `class/`
- `admin/`
- module root controllers
- `ajax/`
- `blocks/`
- `templates/`
- `language/`
- `sql/`
- `preload/`

## 2. Schema model

Install SQL lives in [sql/mysql.sql](/C:/wamp64/www/270test3/htdocs/modules/xcreate/sql/mysql.sql:1).

### `xcreate_categories`

Role:

- category tree
- category metadata
- category display settings

Key concerns:

- slug uniqueness
- parent-child hierarchy
- category-specific templates
- category-level SEO values

### `xcreate_items`

Role:

- core content record

Key concerns:

- category association
- status and publication state
- author linkage
- hits
- item-level SEO values

### `xcreate_fields`

Role:

- schema definition for category-specific dynamic inputs

Key concerns:

- field type
- required/repeatable flags
- conditional visibility
- lookup configuration
- display order

### `xcreate_field_options`

Role:

- option storage for discrete choice fields

### `xcreate_field_values`

Role:

- actual data for dynamic fields

Storage pattern:

- one row per field value instance
- `value_index` supports repeatable ordering
- `value_text` stores plain/delimited values
- `value_file` stores uploaded filenames

### `xcreate_ratings`

Role:

- star voting records per item

## 3. Entity and handler layer

### Category layer

[class/category.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/category.php:1)

- `XcreateCategory`
- `XcreateCategoryHandler`

Key methods:

- `getBySlug()`
- `generateSlug()`
- `getTree()`
- `getParentPath()`
- `hasChildren()`

### Item layer

[class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:1)

- `XcreateItem`
- `XcreateItemHandler`

Key methods:

- `getItemsByCategory()`
- `getRecentItems()`
- `getBySlug()`
- `generateSlug()`
- `updateHits()`
- `getFieldValues()`
- `saveFieldValues()`
- `handleFileUpload()`

### Field layer

[class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:1)

- `XcreateField`
- `XcreateFieldHandler`

Key responsibilities:

- field metadata
- field type registry
- condition engine generation
- field HTML rendering
- option handling

### Group layer

[class/group.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/group.php:1)

- `XcreateGroup`
- `XcreateGroupHandler`

Key responsibilities:

- grouping fields by category
- returning grouped field structures
- assigning and clearing group relationships

### Rating layer

[class/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/rating.php:1)

`XcreateRatingHandler` is a custom aggregation/service class rather than a persistable-object handler.

Key responsibilities:

- ensure ratings table exists
- aggregate stats
- find user vote state
- save or update votes

### Slug layer

[class/slug.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/slug.php:1)

Key responsibilities:

- transliteration-friendly slug creation
- slug uniqueness
- item and category URL helpers

## 4. Helper split

The most important helper split in the module is:

- field-definition and form rendering in `XcreateFieldHandler`
- field-display shaping in `XcreateFieldsHelper`

[class/fields_helper.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/fields_helper.php:1) converts stored values into template-ready structures.

It provides:

- `buildFields()`
- `assignItemFields()`
- `appendFieldsToList()`

This is the current presentation adapter layer of the module.

## 5. Public controller model

### `index.php`

[index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)

Responsibilities:

- resolve category context
- parse `xcf_*` filters
- choose template
- assign category SEO values
- query items
- shape list arrays for Smarty
- append field data to lists

This controller doubles as:

- homepage list
- category list
- filtered list

### `item.php`

[item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)

Responsibilities:

- resolve item by slug or id
- choose detail template based on category
- assign item SEO values
- enforce status visibility rules
- update hits
- build breadcrumb
- assign field helper output
- assign rating payload

### `submit.php`

[submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)

Responsibilities:

- frontend item create/edit
- validation of required fields
- conditional-field evaluation
- repeatable value handling
- file/image/gallery processing
- save into item and field-value tables

## 6. Admin controller model

The admin layer is page-controller oriented and mixes routing, validation, HTML building, and persistence.

### `admin/categories.php`

Handles:

- category CRUD
- category image upload
- custom template bootstrapping
- category SEO persistence

### `admin/fields.php`

Handles:

- field CRUD
- option CRUD
- conditional rules
- lookup selector configuration

### `admin/items.php`

Handles:

- admin item CRUD
- approval
- dynamic form rendering
- item SEO persistence

### `admin/groups.php`

Handles:

- group CRUD
- category/group assignment for fields

### `admin/import.php` / `admin/export.php`

Handle:

- bulk data movement
- field-aware import/export formats

## 7. Template shaping

The main view contracts are:

### Item detail

Template expects data such as:

- `item`
- `category`
- `breadcrumb`
- `field`
- `custom_fields`
- `rating`
- `seo`

### List pages

Template expects:

- `items`
- `categories`
- optional `current_category`
- filter-related structures

### Search page

[templates/xcreate_search.tpl](/C:/wamp64/www/270test3/htdocs/modules/xcreate/templates/xcreate_search.tpl:1)

This template expects a namespaced search model under `xcreate_search`.

### Block templates

Block templates consume arrays built by `blocks/*.php`, often enriched through `XcreateFieldsHelper::appendFieldsToList()`.

## 8. Upload architecture

Uploads are currently implemented in multiple places.

Main paths:

- [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:49)
- [class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:229)
- admin form controllers for categories and items

Current characteristics:

- storage under `/uploads/xcreate/`
- image resizing support
- gallery support through grouped filename handling
- field-value split between `value_text` and `value_file`

Architectural note:

- this should ideally be one shared upload service
- today it is duplicated across several controllers and handlers

## 9. AJAX and interactivity layer

Endpoints:

- [ajax/rating.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/rating.php:1)
- [ajax/lookup.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/lookup.php:1)
- [ajax/get_cat_fields.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/get_cat_fields.php:1)
- [ajax/search_suggest.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/ajax/search_suggest.php:1)

Role of this layer:

- voting
- dynamic lookup selection
- dynamic field metadata retrieval
- search suggestions

## 10. Blocks as presentation adapters

The block layer lives in:

- [blocks/xcreate_blocks.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_blocks.php:1)
- [blocks/xcreate_filter_block.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_filter_block.php:1)
- [blocks/xcreate_widgets.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/blocks/xcreate_widgets.php:1)

Architecture role:

- expose content without full-page controllers
- reuse the item/category/helper stack
- provide alternative access paths into the same data model

## 11. Module registration and metadata

[xoops_version.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/xoops_version.php:1) registers:

- tables
- templates
- preferences
- blocks
- menu information

Important detail:

- it also scans `templates/*.tpl` dynamically to register additional templates

## 12. Preload and lifecycle behavior

### Preload

[preload/preload.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/preload/preload.php:1)

Current behavior:

- adds the module plugin directory to Smarty during header start

### Install/update lifecycle

What exists:

- install SQL
- several SQL update scripts under `sql/`

What does not exist in a strong formal sense:

- robust install/update callback flow under `include/oninstall.inc.php` or `include/onupdate.inc.php`

Also note:

- some schema evolution still happens in runtime request paths through `SHOW COLUMNS` plus `ALTER TABLE` or `CREATE TABLE IF NOT EXISTS`

That is a structural weakness of the current implementation.

## 13. Dynamic content model in practice

The module’s central pattern is:

1. category defines the content context
2. fields define the schema for that category
3. item stores the common record
4. field values store the per-item dynamic data
5. helpers shape the data for templates

This is the essential pattern to reuse if you build a similar system.

## 14. How to build something similar

### Start with the schema

Design these first:

- category table
- item table
- field-definition table
- option table
- field-value table

### Separate persistence from rendering

Have dedicated layers for:

- field definitions
- stored values
- form rendering
- display rendering

### Normalize the view model

Decide early what every template receives. Avoid page-specific array shapes drifting apart over time.

### Centralize uploads

Create one upload service for:

- extension checks
- MIME validation
- unique naming
- image resizing
- gallery handling
- secure destination rules

### Centralize schema migrations

Do not alter tables in live request paths. Use versioned install/update hooks.

### Keep controllers thin

Prefer:

- one service for queries
- one service for uploads
- one service for field rendering
- one view-model builder

## 15. What to preserve if you refactor

The strongest ideas worth preserving are:

- category-based field modeling
- repeatable field value indexing
- helper-driven template enrichment
- slug support
- block reuse of the same content model
- category-specific templates

## 16. What to improve if you modernize

The most important architectural improvements would be:

- remove runtime schema mutation
- split oversized controllers by action
- centralize uploads
- unify escaping rules
- unify list/detail/block view models
- move more inline JS/CSS into assets
- formalize lifecycle hooks and migrations
