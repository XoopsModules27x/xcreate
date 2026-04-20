# Xcreate Diagrams

This document gives a compact schema and request-flow view of the module.

## 1. Schema diagram

```mermaid
erDiagram
    xcreate_categories ||--o{ xcreate_items : contains
    xcreate_categories ||--o{ xcreate_fields : defines
    xcreate_items ||--o{ xcreate_field_values : stores
    xcreate_fields ||--o{ xcreate_field_values : receives
    xcreate_fields ||--o{ xcreate_field_options : offers
    xcreate_items ||--o{ xcreate_ratings : collects

    xcreate_categories {
        int cat_id PK
        int cat_pid
        varchar cat_name
        varchar cat_slug
        varchar cat_template
        varchar cat_list_template
    }

    xcreate_items {
        int item_id PK
        int item_cat_id FK
        varchar item_title
        varchar item_slug
        int item_status
        int item_uid
    }

    xcreate_fields {
        int field_id PK
        int field_cat_id FK
        varchar field_name
        varchar field_label
        varchar field_type
        int field_repeatable
    }

    xcreate_field_options {
        int option_id PK
        int option_field_id FK
        varchar option_value
        varchar option_label
    }

    xcreate_field_values {
        int value_id PK
        int value_item_id FK
        int value_field_id FK
        int value_index
        text value_text
        text value_file
    }

    xcreate_ratings {
        int rating_id PK
        int rating_item_id FK
        int rating_uid
        varchar rating_ip
        int rating_score
    }
```

## 2. Public request flow

```mermaid
flowchart TD
    A[index.php] --> A1[Resolve category by slug or id]
    A1 --> A2[Parse xcf_* filters]
    A2 --> A3[Query items]
    A3 --> A4[Append dynamic field data]
    A4 --> A5[Render list template]

    B[item.php] --> B1[Resolve item by slug or id]
    B1 --> B2[Load category]
    B2 --> B3[Assign SEO data]
    B3 --> B4[Increment hits]
    B4 --> B5[Build field display data]
    B5 --> B6[Build rating data]
    B6 --> B7[Render item template]

    C[submit.php] --> C1[Load category fields]
    C1 --> C2[Validate input]
    C2 --> C3[Process repeatable values]
    C3 --> C4[Process uploads]
    C4 --> C5[Save item]
    C5 --> C6[Save field values]
    C6 --> C7[Redirect]
```

## 3. Admin request flow

```mermaid
flowchart TD
    AC[admin/categories.php] --> AC1[Category CRUD]
    AC1 --> AC2[Category SEO]
    AC2 --> AC3[Template bootstrap]

    AF[admin/fields.php] --> AF1[Field CRUD]
    AF1 --> AF2[Options CRUD]
    AF2 --> AF3[Conditional rules]
    AF3 --> AF4[Lookup settings]

    AI[admin/items.php] --> AI1[Item CRUD]
    AI1 --> AI2[Approval workflow]
    AI2 --> AI3[Item SEO]
    AI3 --> AI4[Dynamic field form rendering]

    AG[admin/groups.php] --> AG1[Group CRUD]
    AG1 --> AG2[Field and group assignments]
```

## 4. Rendering flow

```mermaid
flowchart LR
    A[Handler query] --> B[Controller array building]
    B --> C[XcreateFieldsHelper]
    C --> D[Smarty assignment]
    D --> E[Template render]
```

## 5. Block flow

```mermaid
flowchart LR
    A[Block function] --> B[Item and category handlers]
    B --> C[Optional XcreateFieldsHelper enrichment]
    C --> D[Block array]
    D --> E[Block template]
```

## 6. Files worth reading with the diagrams

- [sql/mysql.sql](/C:/wamp64/www/270test3/htdocs/modules/xcreate/sql/mysql.sql:1)
- [class/item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/item.php:1)
- [class/field.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/field.php:1)
- [class/fields_helper.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/class/fields_helper.php:1)
- [index.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/index.php:1)
- [item.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/item.php:1)
- [submit.php](/C:/wamp64/www/270test3/htdocs/modules/xcreate/submit.php:1)
