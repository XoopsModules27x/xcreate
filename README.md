# Xcreate - Dynamic Content Creation Module for XOOPS

**Version:** 1.51  
**Author:** Eren - Aymak  
**License:** GPL  
**XOOPS:** 2.5.11+  
**PHP:** 7.4 - 8.2

---

## 🎯 What Is Xcreate?

Xcreate is a professional content management module developed for XOOPS. It lets you build flexible content structures with category-based dynamic custom fields.

### Key Features

✅ **Dynamic Content Structure** - Different field sets for each category  
✅ **14 Different Field Types** - Text, textarea, select, checkbox, radio, date, file, image, and more  
✅ **Category Hierarchy** - Unlimited subcategory support  
✅ **Custom Templates** - Custom view for each category  
✅ **Repeatable Fields** - Enter multiple values for the same field  
✅ **File Management** - Image and file upload system  
✅ **Multi-language** - Turkish and English language support  
✅ **User Permissions** - Group-based authorization  

---

## 🚀 Quick Installation

### 1. Upload the Module
```bash
# Extract the ZIP file into the XOOPS root directory
unzip xcreate.zip
mv xcreate modules/

# Set permissions
chmod -R 755 modules/xcreate
```

### 2. Install the Module
1. Log in to the XOOPS admin panel
2. Go to **System > Modules**
3. Find the **Xcreate** module
4. Click the **Install** button

### 3. Initial Configuration
1. **Xcreate > Categories** - Create your first category
2. **Xcreate > Custom Fields** - Define fields for the category
3. **Xcreate > Items** - Start adding content

---

## 📋 Field Types

| Field Type | Description | Use Case |
|-----------|-------------|----------|
| **text** | Single-line text | Title, name, phone |
| **textarea** | Multi-line text | Description, address |
| **editor** | HTML editor | Rich content |
| **select** | Dropdown list | Making a selection |
| **checkbox** | Multiple choice | Multiple options |
| **radio** | Single choice | One option |
| **date** | Date picker | Birth date, date |
| **datetime** | Date + Time | Event time |
| **number** | Numeric input | Price, quantity |
| **email** | Email address | Contact |
| **url** | Web address | Link |
| **color** | Color picker | Theme color |
| **image** | Image upload | Visual content |
| **file** | File upload | Document, PDF |

---

## 💡 Usage Examples

### Example 1: Real Estate Listings

**Category:** Apartments for Sale

**Custom Fields:**
- Price (`number`)
- Square Meters (`number`)
- Room Count (`select: 1+1, 2+1, 3+1, 4+1`)
- Floor (`number`)
- Heating (`select: Combi Boiler, Central, Air Conditioning`)
- Orientation (`radio: North, South, East, West`)
- Features (`checkbox: Elevator, Parking, Balcony, Security`)
- Photos (`image` - repeatable)

### Example 2: Job Listings

**Category:** Software

**Custom Fields:**
- Position (`text`)
- Experience (`select: 0-2 years, 2-5 years, 5+ years`)
- Salary Range (`text`)
- Company (`text`)
- Work Type (`radio: Office, Remote, Hybrid`)
- Skills (`checkbox` - repeatable)
- Application Date (`date`)
- Logo (`image`)

### Example 3: Product Catalog

**Category:** Electronics

**Custom Fields:**
- Brand (`select`)
- Model (`text`)
- Price (`number`)
- Stock (`number`)
- Color Options (`checkbox`)
- Warranty Period (`number`)
- Product Images (`image` - repeatable)
- Technical Document (`file`)

---

## 🎨 Template System

### Default Templates

Xcreate uses the following template files:
- `xcreate_index.tpl` - Main page list
- `xcreate_item.tpl` - Content detail page
- `xcreate_submit.tpl` - Content submission form

### Creating a Custom Template

You can define a custom template for each category:

1. While editing a category, enter the template name in the "Custom Template" field (example: `real-estate`)
2. The template is automatically created at `templates/real-estate.tpl`
3. Customize the template

**Example Template Code:**
```smarty
<div class="xcreate-item">
    <h1>{$item.title}</h1>
    
    <div class="item-description">
        {$item.description}
    </div>
    
    {if $custom_fields}
    <div class="custom-fields">
        {foreach item=field from=$custom_fields}
        <div class="field-group">
            <label>{$field.label}:</label>
            <div class="field-values">
                {foreach item=value from=$field.values}
                <span class="field-value">{$value}</span>
                {/foreach}
            </div>
        </div>
        {/foreach}
    </div>
    {/if}
</div>
```

---

## 🔧 Repeatable Fields

Repeatable fields allow users to enter multiple values for the same field.

### Use Cases:
- Multiple phone numbers
- Multiple image uploads
- More than one email address
- Multiple social media links

### How to Enable:
1. Check the "Repeatable" box when creating the custom field
2. The user can add another field in the form with the "+ Add" button
3. In the template, all values are provided as an array

**Usage in a Template:**
```smarty
{foreach item=phone from=$field.values}
<a href="tel:{$phone}">{$phone}</a>
{/foreach}
```

---

## 🔐 Permissions and Security

### User Permissions
- **View:** Which categories they can see
- **Submit Content:** Which categories they can add content to
- **Edit:** They can edit their own content
- **Delete:** They can delete their own content

### Admin Permissions
- Full access to all categories
- Manage all content
- Define custom fields
- Configure permission settings

### Security Measures
✅ SQL Injection protection  
✅ XSS (Cross-Site Scripting) protection  
✅ CSRF token validation  
✅ File upload security  
✅ Extension validation  
✅ Size limit  

---

## 📊 Database Structure

### Tables

**xcreate_categories**
- Category information
- Hierarchical structure (`parent_id`)
- Custom template information

**xcreate_fields**
- Custom field definitions
- Field type and properties
- Sort order information

**xcreate_field_options**
- `select`, `checkbox`, `radio` options

**xcreate_items**
- Content records
- Category relationship
- Status information

**xcreate_field_values**
- Field values
- Multi-value support
- File information

---

## 🎛️ Module Settings

Configurable settings available from the admin panel:

| Setting | Description | Default |
|--------|-------------|---------|
| **Items Per Page** | Number of items to show on list pages | 10 |
| **User Submission** | Allow users to submit content | Yes |
| **Maximum File Size** | Maximum uploadable file size (KB) | 2048 |
| **Allowed Extensions** | Uploadable file types | jpg,jpeg,png,gif,pdf,doc,docx |

---

## 🔄 Upgrade

### Upgrading to v1.51 (from previous Customfields)

1. **Create a backup:**
```bash
cp -r modules/customfields modules/customfields.backup
mysqldump -u root -p xoops_db > backup.sql
```

2. **Update the files:**
```bash
unzip xcreate.zip
rm -rf modules/customfields
mv xcreate modules/
```

3. **Update the database:**
```sql
-- Rename the tables
RENAME TABLE customfields_categories TO xcreate_categories;
RENAME TABLE customfields_fields TO xcreate_fields;
RENAME TABLE customfields_field_options TO xcreate_field_options;
RENAME TABLE customfields_items TO xcreate_items;
RENAME TABLE customfields_field_values TO xcreate_field_values;
```

4. **Update the module in XOOPS:**
   - Admin > Modules > Xcreate > Update

---

## 🐛 Troubleshooting

### Delete Action Does Not Work

**Solution:** Fixed in v1.51. If the problem still exists:
```php
// Enable debug mode
define('XOOPS_DEBUG_MODE', 1);

// Check database errors
echo $xoopsDB->error();
```

### Constant Already Defined Error

**Solution:** Fixed in v1.51. Clear the cache:
```bash
rm -rf cache/*
rm -rf templates_c/*
```

### File Upload Does Not Work

**Checklist:**
- Is `PHP upload_max_filesize` large enough?
- Does the `uploads/xcreate` folder exist?
- Are the permissions correct? (`755`)
- Is the file extension in the allowed list?

---

## 📚 API and Integration

### Smarty Plugin Usage

To display Xcreate data in templates:

```smarty
{* Show the latest 5 items from the category *}
{xcreate cat_id=1 limit=5 assign=items}

{foreach item=item from=$items}
    <h3>{$item.title}</h3>
    <p>{$item.description}</p>
{/foreach}
```

### PHP Usage

```php
// Load the handler
include_once XOOPS_ROOT_PATH . '/modules/xcreate/class/item.php';
$itemHandler = new XcreateItemHandler($xoopsDB);

// Get recent items
$items = $itemHandler->getRecentItems(10);

foreach ($items as $item) {
    echo $item->getVar('item_title');
}
```

---

## 📞 Support and Contact

### Documentation
- **README.md** - This file
- **CHANGELOG.md** - Version history
- XOOPS forums

### Bug Reports
If you experience a problem:
1. Enable XOOPS debug mode
2. Check the PHP error logs
3. Review database errors
4. Report the issue with a detailed description

---

## ✅ Feature List

### ✅ Completed Features
- [x] Category management (hierarchical)
- [x] 14 different field types
- [x] Repeatable fields
- [x] Custom template system
- [x] File and image uploads
- [x] Multi-language support
- [x] User permissions
- [x] Admin panel
- [x] Smarty plugin
- [x] Delete operations (v1.51)
- [x] Bug fixes (v1.51)

### 🔜 Upcoming Features (v1.6)
- [ ] REST API
- [ ] Import/Export
- [ ] Bulk actions
- [ ] Field validation improvements
- [ ] Icon sets
- [ ] More field types

---

## 📜 License

This module is distributed under the GPL (GNU General Public License).

---

## 🙏 Thanks

- To the XOOPS community
- To the users who tested the module
- To everyone who provided feedback

---

**Professional content management with Xcreate! 🚀**

**Version:** 1.51  
**Date:** November 25, 2024  
**Developer:** Eren - Aymak  
**Website:** https://aymak.com.tr
