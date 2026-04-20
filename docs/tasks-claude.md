# Xcreate Module — Code Review & Improvement Checklist

Target: `htdocs/modules/xcreate/` (Xcreate 1.51 → 1.6)
Scope: XOOPS 2.5.11+ compatible, to be kept working on PHP 8.2–8.5 and Smarty 4.
Conventions: XOOPS non‑standard Smarty delimiters `<{ }>`, `_MI_` constants,
`XoopsPersistableObjectHandler`, `$xoopsSecurity` tokens.

Each item is checkable, points to the offending file(s), and states the fix direction.
Critical security items are at the top; the list then descends in severity.

---

## A. Critical — Security vulnerabilities (fix first)

1. [ ] **CSRF protection is explicitly removed from the public item save path.**
   `modules/xcreate/submit.php:174` carries the comment *"Security check kaldırıldı"* and the `$GLOBALS['xoopsSecurity']->check()` call that should guard the `op=save` POST is gone. Re‑add `XoopsFormHidden` token via `XoopsThemeForm(...)` (the form already has token support) and call `$GLOBALS['xoopsSecurity']->check()` on save.

2. [ ] **Admin approve / delete are GET‑based with no CSRF token.** Visiting
   `admin/items.php?op=approve&id=…`, `admin/items.php?op=delete&id=…&ok=1`,
   `admin/categories.php?op=delete&id=…&ok=1`,
   `admin/fields.php?op=delete&id=…&ok=1` mutates state from a plain link. Convert these to POST forms, validate `$GLOBALS['xoopsSecurity']->check()`, and replace `?ok=1` with a proper confirmation form that carries a token.

3. [ ] **Public AJAX rating endpoint has no CSRF token and no rate limit.**
   `ajax/rating.php` accepts `POST {item_id, score}` from any visitor and calls `saveVote()`. Require a token (you already emit `$xoopsSecurity->createToken()` in `item.php:234` — validate it in `ajax/rating.php`), verify `$xoopsUser->isActive()` for member votes, and add a per‑IP/per‑item cooldown.

4. [ ] **`HTTP_X_FORWARDED_FOR` is trusted for rate‑limiting identity.**
   `ajax/rating.php:57` and `item.php:229` build the "guest IP" from the raw `X-Forwarded-For` header. Any client can send this, so duplicate‑vote protection and IP‑keyed abuse logging are meaningless. Only honor the header when a trusted proxy list is configured; otherwise use `REMOTE_ADDR`.

5. [ ] **LIKE wildcard injection + `%` DoS in multiple queries.**
   `ajax/search_suggest.php:37`, `ajax/lookup.php:93`, `search.php:143‑148` and `blocks/xcreate_filter_block.php` escape quotes but do **not** escape `%` and `_`. A user can pass `%%%%%` to trigger a full table scan across text columns (DoS) or abuse pattern matching. Wrap `$xoopsDB->escape($q)` with `addcslashes($q, '%_\\')` before embedding.

6. [ ] **`</script>` breakout in inline JSON injection.**
   `class/field.php:70` does `json_encode($conditions, JSON_UNESCAPED_UNICODE)` and emits it inside an inline `<script>` block. A field label containing `</script>` or `<!--` breaks out of the script context. Always OR in `JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT`. Apply the same fix at `admin/fields.php:383` (`xcreate_build_field_options_json`) and at any JSON emitted via `echo` into an HTML page.

7. [ ] **Path traversal in Smarty `{xcreate template=…}` plugin.**
   `plugins/function.xcreate.php:111‑120` composes
   `XOOPS_ROOT_PATH . '/modules/xcreate/templates/custom/' . $template . '.tpl'`
   without `basename()` or allowlist. A crafted value can escape the directory via `../../`. Call `basename($template)` first and reject anything that does not match `/^[a-z0-9_-]+$/i`.

8. [ ] **Path traversal / arbitrary `.tpl` write in category save.**
   `admin/categories.php:97‑152` and `:154‑206` write to
   `templates/` + `$_POST['cat_template']` (and `cat_list_template`) with only an "ensure `.tpl` extension" check. An admin‑level attacker can create files anywhere below the webroot. Call `basename()`, enforce `/^[a-z0-9_-]+$/i` for the base name, and reject paths that contain `..` or directory separators.

9. [ ] **Generated scaffold templates use the wrong Smarty delimiters.**
   Same block (`admin/categories.php:119-146` and `:177-200`) writes default templates using `{...}` instead of XOOPS `<{...}>`. Every category that triggers this path gets a broken template the first time it is shown. Rewrite the heredocs using `<{…}>`.

10. [ ] **Debug files exposed in admin.** `admin/debug_cats.php`, `admin/debug_items.php`, `admin/debug_log.php` enable `display_errors=1`, re‑include the production controller, or read arbitrary log content. They are reachable by any logged‑in admin. Remove them from the production tree (or guard with `XOOPS_DEBUG` and move under `admin/tools/`); they are explicitly marked "Hatayi gordukten sonra bu dosyayi sil" in their own header.

11. [ ] **Stored XSS in public pages.** `item.php`, `index.php`, and the `xcreate_*` templates render `item_title`, `author`, `cat_name`, `breadcrumb.name`, custom field values, etc. without `|escape`. In XOOPS 2.5/2.7 auto‑escape is **not** applied to every variable — you must add `|escape` (or `|escape:'html'` for attributes) in every `.tpl` where the value is user‑controlled. Audit every `<{$…}>` in `templates/xcreate_item.tpl`, `xcreate_index.tpl`, `xcreate_search.tpl`, `xmodules_icerik.tpl`, `xmodules_liste.tpl`, and all generated custom templates.

12. [ ] **Stored XSS in admin tables.** `admin/index.php:211`, `admin/items.php:902‑904`, `admin/categories.php:247/496‑497`, `admin/fields.php` list views concatenate user‑supplied strings directly into HTML. Wrap with `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')` or switch to templates.

13. [ ] **Stored HTML in `item_description` is rendered raw.**
   `submit.php:324` / `admin/items.php:547` save `$_POST['item_description']` without sanitization; `templates/xcreate_item.tpl:60` and `xcreate_index.tpl:72` print it raw. Run it through XOOPS `myTextSanitizer` with the appropriate flags (`displayTarea`) at render time, or purify it at save time with an allowlist (HTML Purifier is already used elsewhere in XOOPS).

14. [ ] **URL scheme not validated in helper‑generated links.**
   `class/fields_helper.php:118‑120` renders `<a href="…">` and `<a href="mailto:…">` using `htmlspecialchars()` alone. A `javascript:` or `data:` URL slips through. Reuse an allowlist (`http, https, mailto, tel, /`) and reject anything else — see the "URL schemes" rule in the repo‑wide security checklist.

15. [ ] **Unbounded, predictable filename + no MIME validation on uploads.**
   `submit.php:85`, `class/item.php:274`, `admin/items.php:602`, `admin/categories.php:219` use `uniqid() . '_' . time() . '.' . $ext`. Extensions are checked but MIME/signature is not, filenames are not `basename()`‑d, and there is no re‑encoding of images. Fixes: (a) `basename()` and allowlist `[a-zA-Z0-9._-]`, (b) check `finfo_file()` against expected MIME, (c) reject double‑extensions (`.php.jpg`, `.phtml`, etc.), (d) re‑encode images through GD to strip polyglot payloads, (e) use `random_bytes(16)` + extension for the stored name.

16. [ ] **Upload directory has no `.htaccess` to block PHP execution.**
   `/uploads/xcreate/` (created at `submit.php:61`, `class/item.php:252`, `admin/categories.php:211`) is created with `0755` but no `.htaccess` drops PHP. Write a minimal `.htaccess` at creation time (or ship one under `uploads/xcreate/.htaccess`) containing `RemoveHandler .php .phtml .php3 .phar`, `<FilesMatch "\.(php|phtml|php\d|phar)$"> Require all denied </FilesMatch>`, and `Options -ExecCGI`.

17. [ ] **`allow_user_submit` is mis‑checked.** `submit.php:45` reads `if (!$xoopsModuleConfig['allow_user_submit'] && !$xoopsUser)` — i.e. the block only fires for anonymous users. A logged‑in non‑admin can submit even when the admin turned the toggle off. Rewrite to `if (!$xoopsModuleConfig['allow_user_submit'] && (!$xoopsUser || !$xoopsUser->isAdmin()))`.

18. [ ] **No category existence/permission check on submit.**
   `submit.php:177‑322` casts `$_POST['item_cat_id']` to int but never verifies the category exists, is not a draft, or that the current user is allowed to submit into it. Load the category via `XcreateCategoryHandler::get()` and fail closed when it is new/deleted; enforce group permissions once [#30](#30) is implemented.

19. [ ] **Ownership check happens after category validation on edit.**
   `submit.php:304‑309` loads the target item only inside the `if ($item_id > 0)` branch and then applies ownership. A non‑owner still passes category/title validation on the same request, which is information disclosure. Load + ownership‑check before validation.

20. [ ] **Category delete recursion uses `implode(',', $ids)` without a cast step.**
   `admin/categories.php:297‑317` builds `$cat_ids_str = implode(',', $all_cat_ids)` where the values come from `$categoryHandler` (objects) but later from the `getVar('cat_id')` call. Cast each element with `intval()` before `implode()` so a malformed object cannot poison the `IN (…)` clause.

21. [ ] **`field_condition` JSON stored from admin is not validated.**
   `admin/fields.php:108‑118` stores `json_encode($condition_data)` where `condition_operator`, `condition_value`, `condition_field_id` come from `$_POST` with no whitelist. Allow operators only from `{==, !=, contains, not_empty}` (the same set the JS engine handles) and reject anything else.

22. [ ] **Runtime `ALTER TABLE` on every request.** `submit.php:26‑29`, `admin/items.php:35‑47`, `admin/fields.php:33‑45`, `admin/index.php:30‑61`, `class/rating.php:39‑69`, `class/group.php:42‑66` all run `SHOW COLUMNS … LIKE` + `ALTER TABLE` from the hot path. That is (a) a race condition on concurrent requests, (b) blocks the request for admins whose DB user lacks `ALTER` rights (silent failure via `@`), (c) leaves the schema version implicit. Move all migrations into `sql/mysql_update_*.sql` files driven by `$modversion['sqlfile']['mysql_update']` and an `xoops_module_update_xcreate()` callback in `include/onupdate.inc.php`.

23. [ ] **Error suppression hides DB failures.** `admin/index.php:41/55`, `ajax/rating.php:8‑12`, `ajax/get_cat_fields.php`, `ajax/lookup.php`, `ajax/search_suggest.php` use `@` and `error_reporting(0)`. Remove the `@`/`error_reporting(0)` pairs, capture real errors through `XoopsLogger` or PSR‑3, and only suppress the output buffer (not the errors).

24. [ ] **`logger.php` writes under the web root.** `class/logger.php:11‑16` creates `modules/xcreate/logs/debug_YYYY-MM-DD.log` which is inside `XOOPS_ROOT_PATH` — a mis‑configured vhost can serve it. Move logs to `XOOPS_VAR_PATH . '/logs/xcreate/'`, and drop a `.htaccess` that denies all.

25. [ ] **`error_log($message)` duplicates sensitive values into the PHP log.**
   `class/logger.php:29`. Replace with a PSR‑3 logger (XMF ships with a Monolog adapter) and add log rotation.

26. [ ] **Rate‑limiter table is not declared in `xoops_version.php`.**
   `xcreate_ratings` is created at runtime (`class/rating.php:53‑69`) but not listed under `$modversion['tables']` — uninstalling the module never drops it. Add the table to `$modversion['tables']` and move the `CREATE TABLE` into `sql/mysql.sql`.

27. [ ] **Guest unique‑vote constraint cannot be enforced.**
   `class/rating.php:61` declares `UNIQUE KEY unique_member_vote (rating_item_id, rating_uid)` — all guests share `rating_uid=0`, so the DB never blocks a duplicate guest vote. Either (a) drop guest voting, or (b) widen the unique index to `(rating_item_id, rating_uid, rating_ip)` and accept IP coarseness, or (c) keep a server‑side session/fingerprint hash and index on that.

28. [ ] **No transaction on cascading deletes.**
   `admin/items.php:115‑120`, `admin/categories.php:303‑337`, `admin/fields.php:198‑204` issue 3‑5 sequential `DELETE` statements without a transaction. If any fails, the DB ends up with orphaned rows (field_values without an item, options without a field). Wrap each cascade in `START TRANSACTION` / `COMMIT` via `$xoopsDB->startTrans()` / `commit()` (or raw `queryF` equivalents), and roll back on any failure. Consider switching to FK + `ON DELETE CASCADE` in `sql/mysql.sql`.

29. [ ] **Saved select/radio/checkbox options are not escaped at display time.**
   `class/field.php:347/363/380` HTML‑escape at render, but `admin/fields.php:572‑573` prints them into the option list without escaping when they come from the `existing_options` loop — the `htmlspecialchars($option['value'])` there is missing `ENT_QUOTES` so attribute‑context injection via single quote is still possible.

30. [ ] **Group permissions advertised in README don't exist in code.** The README claims "Kullanıcı İzinleri — Grup bazlı yetkilendirme" but neither `xoops_version.php` nor the controllers register any XOOPS group permission (`mod_read`, `xcreate_cat_view`, etc.). Implement group‑based read/submit permissions via the `$modversion['permissions']` block, and check with `XoopsGroupPermHandler::checkRight()` in `index.php`, `item.php`, `submit.php`.

---

## B. High — Correctness and data integrity

31. [ ] **Duplicate `initVar()` calls in `XcreateCategory`.**
   `class/category.php:21‑31` initializes the five `cat_meta_*` properties twice. The second pair silently overwrites the first and makes future migrations error‑prone. Delete the duplicated block.

32. [ ] **`admin/categories.php:62` assigns `$_POST['cat_pid']` unvalidated.**
   The raw POST value is pushed into `cat_pid` without `intval()`. Add `intval()` (and validate the parent exists and is not a descendant of the current category to prevent loops).

33. [ ] **`cat_weight`, `item_cat_id`, `item_status` etc. are stored without `intval()`.**
   `admin/categories.php:85`, `submit.php:322‑323`, `admin/items.php:545` pass raw POST. Cast every integer field before `setVar()` or rely on `XoopsObject::cleanVars()` with an explicit data type — don't trust the data‑type hint alone because XOBJ_DTYPE_INT still stores string input as‑is on many builds.

34. [ ] **Redirect targets are user‑controlled in error paths.**
   `submit.php:298` concatenates `'submit.php?cat_id=' . $_POST['item_cat_id']` into a `redirect_header()` URL. The value is intval'd on the next request, but the URL still reflects the user's input — shape it from the already‑validated `$cat_id` local.

35. [ ] **`$_SERVER['REMOTE_ADDR']` reached without `isset()`.**
   `item.php:229‑231` uses `$_SERVER['REMOTE_ADDR']` directly. If a request comes in via CLI or a mis‑configured FCGI worker it is undefined. Guard with `?? ''`.

36. [ ] **`XoopsPageNav::renderNav()` receives a `$page_extra` with no URL encoding of aggregated filter values.**
   `index.php:170‑177` builds pagination query strings by concatenating raw `$val` — `urlencode()` is applied to filters but not to ranges (`xcf_5_min=.5`). This breaks pagination when a filter contains `&`.

37. [ ] **`getBySlug()` in `XcreateItemHandler` and `XcreateCategoryHandler` builds SQL by string concatenation after `escape()`.**
   `class/item.php:76‑88`, `class/category.php:47‑59`. The escaping is correct, but this is a recurring pattern. Replace with a `Criteria('item_slug', $slug)` on the handler — the parent class already knows how to quote strings safely.

38. [ ] **`XcreateItemHandler::updateHits()` is race‑prone on hot items.**
   `class/item.php:109` runs `UPDATE … SET item_hits = item_hits + 1`. OK for low traffic, but the query is not throttled — the same visitor pressing F5 fifty times inflates hits. Add a per‑session/IP debounce and/or move hit tracking out of the render path (deferred write).

39. [ ] **`XcreateFieldsHelper::buildFields()` runs a handler `new … Handler()` per call + re‑queries options.** Called from `index.php` (list), `item.php` (detail), `blocks/xcreate_blocks.php` and `xcreate_widgets.php`. The N+1 pattern loads every field + option rows for every item. Batch: fetch all `xcreate_fields` for the category once, preload `xcreate_field_options` once, then reuse.

40. [ ] **N+1: `new XoopsUser($item->getVar('item_uid'))` per item.**
   `index.php:256`, `admin/index.php:207`, `admin/items.php:884`, `blocks/xcreate_blocks.php:36`, `blocks/xcreate_widgets.php:47‑55`. Batch‑load uids with a single `XoopsMemberHandler::getUsers()` call and index by uid.

41. [ ] **N+1: rating stats for every item.**
   `index.php:272` calls `$ratingHandler->getStats($item_id)` inside the list loop, which is two queries per item. Add a bulk method that accepts an array of item IDs and returns a map.

42. [ ] **Filter SQL uses `FIND_IN_SET` on free‑text `value_text`.**
   `index.php:192`, `blocks/xcreate_filter_block.php:73`. `FIND_IN_SET` treats the column as a CSV; commas inside a user‑submitted value silently break filtering. Either normalize multi‑select values to a dedicated join table, or store each value in its own row (`value_index` is already there) and query via an `IN (…)` subquery.

43. [ ] **Saving field values deletes then re‑inserts every time.**
   `class/item.php:155‑237` — `DELETE` then `INSERT` regardless of whether any value changed. On a 30‑field form this creates churn and defeats revision tracking. At minimum wrap in a transaction; at best diff and update.

44. [ ] **File/gallery detection is fragile.** `class/item.php:195‑210` decides "is this a filename?" with a regex against a hard‑coded extension list. A value like `report_2025.docx` from a text field is misclassified as a file and written to `value_file`. Track the field type explicitly (you already have it in `$field->getVar('field_type')`) and branch on that.

45. [ ] **`logger.php::log()` opens the same file every call with `file_put_contents(FILE_APPEND)`.** Under concurrent writes the log can interleave partial lines. Use `LOCK_EX` or switch to Monolog with a `RotatingFileHandler`.

46. [ ] **`ajax/search_suggest.php` has no `isset()` guard for `$res`.**
   If `$xoopsDB->query()` fails, `while ($xoopsDB->fetchArray($res))` loops on `false` and emits an E_WARNING. Always test `$res` before iterating.

47. [ ] **`admin/items.php:57‑70` — approve path inserts the object via `insert()` without checking the row is currently in `pending` state.** An approve link can be replayed endlessly and resets `item_published`. Gate on `item_status == 0` before re‑approving.

48. [ ] **Admin item list does not honor `status_filter = 2` (Rejected).**
   `admin/items.php:889‑898` only handles 0 and 1 even though the delete confirmation and README both mention a "Reddedildi" state. Pick one: delete the rejected state everywhere, or wire it end‑to‑end (status filter, badge, approve/reject actions).

49. [ ] **Upload allowlist is copy‑pasted inconsistently.**
   `submit.php:68` allows `jpg jpeg png gif webp` for images and `pdf doc docx xls xlsx zip rar txt csv` for files. `class/item.php:260` reads from module config. `admin/items.php:600` and `admin/categories.php:216` hard‑code their own sets. Centralize in a single helper (e.g. `XcreateUploadHelper::allowedExtensions($type)`) driven by the module config.

50. [ ] **`ajax/get_cat_fields.php` ignores the passed JSON‑encoded admin token.** It only checks `$xoopsUser->isAdmin()`. Validate the XOOPS security token on the admin JSON endpoints too, so a logged‑in admin can't be tricked into a CSRF‑leaked admin list via a malicious page.

---

## C. Medium — Architecture, performance, and maintainability

51. [ ] **Add database indexes identified by the filter/search workloads.**
    At minimum: `xcreate_items(item_status, item_cat_id, item_created)` compound, confirm `xcreate_field_values(value_field_id, value_item_id)` exists, add `xcreate_field_values(value_item_id, value_field_id, value_index)` so `getFieldValues()` can do an index‑only scan, and `xcreate_ratings(rating_item_id, rating_score)` for distribution queries.

52. [ ] **`sql/mysql.sql` charset is inconsistent with XOOPS 2.7 defaults.** Core uses `utf8mb4` + `utf8mb4_unicode_ci`; the rating table in `class/rating.php:65` creates `DEFAULT CHARSET=utf8`. Convert all xcreate tables to `utf8mb4`. Also add `DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci` in `sql/mysql.sql`.

53. [ ] **`item_slug` and `cat_slug` are declared `UNIQUE` but allow empty strings.** `sql/mysql.sql:16, 32` mark them `UNIQUE KEY` with `DEFAULT ''`. MySQL allows only one empty‑string row, so the second unsaved item silently fails to insert. Either make the column `NULL` and rely on `UNIQUE` (MySQL permits multiple NULLs), or refuse empty slugs at the handler level.

54. [ ] **Introduce foreign keys with `ON DELETE CASCADE`.** `sql/mysql.sql` has zero FKs even though the tables are clearly relational (items→categories, field_values→items, field_values→fields, field_options→fields, ratings→items). FKs let you delete the many hand‑written cascade blocks in `admin/*.php` and remove the associated orphan risk ([#28](#28)).

55. [ ] **Every access of `$_GET`/`$_POST`/`$_REQUEST` is raw.** Switch to `\Xmf\Request::getInt/getString/getArray()` etc. across `index.php`, `item.php`, `search.php`, `submit.php`, and every `admin/*.php`. It produces consistent casting, reduces `isset()` noise, and makes PHPStan happier.

56. [ ] **Centralize direct `$xoopsDB->query()` string SQL behind criteria builders.** Hand‑written SQL in `index.php`, `search.php`, `ajax/*.php`, `blocks/xcreate_filter_block.php`, `plugins/function.xcreate.php` is repetitive and brittle. Move to dedicated handler methods (`getFilteredItems(CriteriaCompo)`, `searchItems(array $args)`) that return result sets.

57. [ ] **`query()` vs `queryF()` usage is mixed.** Writes should use `queryF` (or `exec` in 2.7+); reads should use `query`. Several files use `queryF` for reads (e.g. schema introspection) and `query` for writes — audit and align. See the XOOPS database skill rules for the distinction.

58. [ ] **Prefer `Xmf\Module\Helper` + DI over `global $xoopsDB`.** Almost every class in `class/` uses `global $xoopsDB` or `$GLOBALS['xoopsDB']`. Inject the DB in the constructor (they already accept `$db`, but the helpers bypass it with `global`). Remove the `global` statements in `saveFieldValues()`, `getFieldValues()`, `getFieldOptions()`, etc.

59. [ ] **`XcreateSlug::create()` strips every non‑ASCII character after the Turkish map.** Line 28 `preg_replace('/[^a-z0-9\s\-]/', '', $text)` drops Cyrillic, Chinese, Arabic, emoji, etc., so non‑Turkish sites produce empty slugs and rely on the `'item'`/`'kategori'` fallback. Use `Xmf\StringUtil::slug()` or `intl`'s `Transliterator::create('Any-Latin; Latin-ASCII; Lower()')` to cover every script XOOPS supports.

60. [ ] **`XcreateSlug::makeUnique()` has an unbounded `do…while`.** `class/slug.php:47‑64` has no maximum iteration; a pathological dataset (millions of collisions) can spin forever. Cap at e.g. 1000 attempts and fall back to a random suffix.

61. [ ] **`xoops_version.php` scans `templates/*.tpl` via `glob()` every load.** Lines 58‑71 scan the directory on every module upgrade cycle, including demo files (`deneme.tpl`, `ornek-kullanim.html`, `xcreate_item_ORNEK_KULLANIM.tpl`). Ship an explicit list of production templates and delete the demo artifacts from the distribution.

62. [ ] **Missing `module.json` (XOOPS 2.7 dual manifest).** `xoops_version.php` is the legacy manifest; 2.7 prefers `module.json` next to it for composer metadata. Add one that mirrors `$modversion` so packaging tools can read the module.

63. [ ] **Inline CSS/JS in controllers.** `submit.php:584‑647`, `admin/items.php:131, 322‑504`, `admin/fields.php:237‑271`, `admin/categories.php:119‑146`, `admin/groups.php:98‑138` emit tens of kilobytes of CSS and JS with `echo`. Move to versioned files under `assets/css/` and `assets/js/` and enqueue with `<link>/<script>` tags. Besides maintainability, the inline blobs re‑escape poorly when a `'` or `"` lands in a translated string.

64. [ ] **Heavy `echo '<div …>'` admin dashboards.** `admin/index.php` dashboards build pages as PHP string concatenation. Migrate to an admin template under `templates/admin/` so `_MI_XCREATE_*` / `_AM_XCREATE_*` translations apply and theming works.

65. [ ] **Split oversized controllers.** `admin/items.php` (53 kB), `admin/fields.php` (40 kB), `submit.php` (38 kB), `admin/categories.php` (25 kB), `admin/import.php` (20 kB), `admin/groups.php` (20 kB) each mix routing, validation, SQL, HTML, and uploads. Extract controllers per action (`SaveItemController`, `DeleteItemController`, …) and a shared `XcreateFormBuilder`.

66. [ ] **`XcreateFieldHandler::renderField()` builds ~150 lines of inline HTML.** Refactor into per‑type render methods on a `FieldRenderer` that receives a typed DTO. Bonus: templating the per‑type HTML makes theme overrides possible without touching PHP.

67. [ ] **Template filenames include demo/sample content.** `templates/deneme.tpl`, `xcreate_tanitim-tr.html`, `xcreate_tanitim_en.html`, `ornek-kullanim.html`, `xcreate_index_ORNEK_KULLANIM.tpl`, `xcreate_item_ORNEK_KULLANIM.tpl`, `yildizlioylamakullanimi.tpl` ship with the module. Move them to `docs/examples/` (or drop). Admins should not see them as selectable templates.

68. [ ] **Search template prints "no results found" when `total > 0`.** `templates/xcreate_search.tpl:104` has the wrong branch. Verify the logic against the `search_performed`/`total`/`results` variables.

69. [ ] **Admin filter block's AJAX endpoint runs on every block render.** `blocks/xcreate_filter_block.php:126‑128` fires `_xcf2_ajax_count()` whenever the file is loaded and `$_POST['xcf2_ajax']='1'`. XOOPS loads block files during normal page rendering — a POST to any page with that parameter produces a JSON response **and** kills the rest of the page. Extract the AJAX handler into `ajax/filter_count.php`.

70. [ ] **Comment/TODO cleanup.** The codebase contains Turkish inline TODOs, dead `if (!empty($validation_errors)) {}` blocks (`submit.php:292‑294`), and stray debug stubs (`admin/items.php:103‑125` comments). Run a sweep and remove once the controllers are refactored.

71. [ ] **Use `match` over `switch` in new code.** `submit.php:213‑219`, `admin/items.php:889‑898`, `class/field.php:198‑334`, `search.php:157‑170` are all candidates. `match` is strict, has no fallthrough, and documents intent. Keep `switch` only where fallthrough is actually desired.

72. [ ] **Typed properties and constructor promotion.** Every `class/*.php` class still declares untyped `public` members and accepts untyped `$db`. Adopt `public function __construct(private XoopsMySQLDatabase $db)` and typed properties — works on PHP 8.2+ and drops a layer of noise.

73. [ ] **Replace `array()` with `[]` for new code.** Both forms are legal but the short form is the current XOOPS 2.7 house style (matches existing `Xoops\Core`).

74. [ ] **Consolidate duplicate field definitions.** `XcreateCategory` has duplicate `cat_meta_*` initVars (see [#31](#31)). Audit all handlers for similar drift with a diff of `initVar()` names.

75. [ ] **`ajax/*.php` endpoints duplicate bootstrap.** Every file re‑implements `mainfile.php` discovery, output‑buffer scrub, `error_reporting(0)`, and a local `exit_json()`. Extract to `class/AjaxBootstrap.php` and require from each handler.

76. [ ] **Move file upload handling to a shared service.**
    `submit.php:50‑103`, `class/item.php:243‑282`, `admin/items.php:596‑607`, `admin/categories.php:209‑226` each re‑implement uploads with subtly different rules. Create `Xcreate\Upload\UploadService` with methods `image(array $file)` and `document(array $file)`.

77. [ ] **`ajax/rating.php` emits `method_not_allowed` with 200.** Return real HTTP status codes (`405` here, `403` for auth failures, `400` for bad params). Makes front‑end handling easier and satisfies API clients.

78. [ ] **Caching headers are inconsistent.** `ajax/rating.php` sends `Cache-Control: no-cache`; `ajax/search_suggest.php` sends no cache header at all (CDN may cache the JSON). Apply a consistent policy for all AJAX responses.

79. [ ] **Remove unused output buffering juggling.** Every ajax handler wraps `include $mainfile;` in `ob_start()/ob_end_clean()`. `mainfile.php` writes nothing unless XOOPS is mis‑configured — document the constraint and drop the scrub once [#23](#23)/[#25](#25) are implemented.

80. [ ] **Pre‑escaping inside controllers while the template re‑escapes.** `index.php:92, 122‑124, 300‑303` call `htmlspecialchars()` on SEO meta values before assigning to Smarty. If the templates add `|escape:'html'` per [#11](#11), the values are double‑escaped. Settle the contract: **assign raw, escape in the template** (see the repo‑wide "assign/escape contract").

81. [ ] **Search `_xcreate_highlight()` builds a regex from `preg_quote($q)` after HTML‑escaping.** `search.php:270‑275` accepts the highlight delimiter inside the pattern; this works but fails on multibyte input when used with `/u`. Verify and add tests.

82. [ ] **Wildcard include of main language files.**
    Every page does the same 5‑line `if (file_exists(language/.../main.php)) include_once …`. Extract to a single helper `xcreate_load_language('main')` so the guard is written once.

83. [ ] **`language/{turkish,english}/main.php` define constants without `if (!defined())` in some cases.** (present in `modinfo.php` but inconsistent across `admin.php` and `main.php`). Adding the guard avoids PHP 8.2 "constant already defined" fatals when templates include the language file twice.

84. [ ] **`README.md` claims SQL Injection, XSS, CSRF protections are in place.** The review found them missing. After fixes, re‑read the README against the code: every claim (✅ **SQL Injection koruması**, ✅ **CSRF token kontrolü**, ✅ **Dosya yükleme güvenliği**, ✅ **Extension kontrolü**, ✅ **Boyut limiti**) must correspond to something the code actually does — otherwise delete/downgrade the claim.

85. [ ] **`modversion['version'] = 1.6` but README says 1.51.** Using a float version is discouraged on PHP 8.x because of locale‑dependent casting. Use a string (`'1.6.0'`) and align README / CHANGELOG.

86. [ ] **`admin/menu.php` duplicates the "Submit" entry.** Lines 38‑42 and 63‑67 add the same link twice. Remove the duplicate.

87. [ ] **Admin menu "Arama Sayfası" link is absolute `/search.php`.** Line 47 — should be `'../xcreate/search.php'` (it currently resolves to the site root `/search.php`, which points to the system search).

88. [ ] **`XcreateLogger::log()` writes date into filename via `date()` without timezone.** On shared hosting the file may flip to the wrong day. Use `gmdate()` or set the timezone at module bootstrap.

---

## D. Low — Style, docs, tests

89. [ ] **Add strict direct‑access guards.** `class/logger.php` has none. Add `if (!defined('XOOPS_ROOT_PATH')) { exit(); }` at the top of every module PHP file.

90. [ ] **PHPDoc `@throws`, `@return` mismatches.** Several public methods omit `@param`/`@return`. Audit with PHPStan level 6+ and add types.

91. [ ] **Remove commented‑out code blocks** (`admin/items.php:103‑112`, `submit.php:36‑37`, empty conditional blocks `if (!empty($validation_errors)) {}`).

92. [ ] **Replace `strlen()` with `mb_strlen()` for user‑facing length checks** where reached (`search.php` already does, but `class/slug.php` and any future validator should). Turkish/CJK text over‑counts with `strlen()`.

93. [ ] **Consolidate "Eren Yumak — Aymak" marker comments** into a single `CREDITS` / composer `authors` entry; remove from every file header.

94. [ ] **Add a `CHANGELOG.md`.** README references a CHANGELOG but no file exists in the repo.

95. [ ] **Ship a composer.json for the module.** Declare `"php": ">=8.2 <8.6"`, `"smarty/smarty": "^4.0"`, and the current `ext-*` requirements (`ext-gd`, `ext-mbstring`, `ext-json`, `ext-fileinfo`). Enables static analysis via `phpstan.neon` with `scanDirectories: [class, admin, ajax, blocks, plugins]`.

96. [ ] **Add PHPStan / Psalm baseline.** Start at level 5, target level 7 over time. Use `vendor/bin/phpstan analyse --memory-limit=1G class admin ajax blocks plugins`.

97. [ ] **Add PHPUnit tests for the pure helpers.** Priority: `XcreateSlug::create()`, `XcreateSlug::makeUnique()` (with a sqlite stub), `XcreateFieldsHelper::buildFields()`, `_xcreate_snippet()`, `_xcreate_highlight()`. These have no XOOPS coupling and are fast to test.

98. [ ] **GitHub Actions matrix: PHP 8.2 / 8.3 / 8.4 / 8.5.** `php -l` on every PHP file, Smarty delimiter check on `*.tpl` (a simple `grep -Pn '(^|[^<]){[^{]' templates/*.tpl` catches standard‑delimiter mistakes like the one in [#9](#9)), and the new PHPUnit suite.

99. [ ] **Add `robots.txt` recommendations.** Document under `docs/` which module paths should be `Disallow:` — `admin/`, `ajax/`, `submit.php`, `uploads/xcreate/`. Don't ship a `robots.txt` in the module (it belongs on the site).

100. [ ] **Document stored‑HTML policy.** Once [#13](#13) is decided, add a short `SECURITY.md` in the module describing which field types accept HTML, what sanitization runs, and how to disable `editor` fields for untrusted roles.

101. [ ] **Remove the `ROOT_HTACCESS_EKLE.txt` note in favor of a proper `docs/INSTALL.md`.** Its current location — next to `xoops_version.php` — looks like junk and confuses packagers.

102. [ ] **Translate README.md into English** (or split `README.tr.md` / `README.md`). All XOOPS community modules are expected to carry English documentation in `README.md`; the Turkish version is a valuable secondary file.

103. [ ] **Align commit and PR messages to Conventional Commits** (`feat:`, `fix:`, `security:`, `compat(php85):` …). Enforce via a `.gitlint` or `commitlint` config and CI step.

---

## E. Added after cross‑review with `tasks-codex.md`

Items the parallel Codex review surfaced that are not already captured above.
Numbering continues from the main list so existing `#nn` references stay stable.

104. [ ] **Stored XSS via `_xcreate_highlight()` on raw titles in search.**
   `search.php:206` builds the results array with
   `'title' => _xcreate_highlight($obj->getVar('item_title'), $q)` —
   `$item_title` is passed in **raw**, then wrapped with `<mark>` tags, then
   stored in `results[n].title` and later printed unescaped in
   `templates/xcreate_search.tpl`. A malicious title breaks straight out.
   Fix at the source: escape the title with `htmlspecialchars()` before
   calling `_xcreate_highlight()`, or refactor `_xcreate_highlight()` to
   accept already‑escaped input only (its doc comment already claims so).
   Distinct from item [#11](#a-critical--security-vulnerabilities-fix-first)
   (general template escape) and [#81](#c-medium--architecture-performance-and-maintainability)
   (multibyte regex concern).

105. [ ] **JSON‑LD `<script type="application/ld+json">` needs strict JSON
   encoding, not `|escape:'javascript'`.**
   `templates/xcreate_item.tpl:22‑24` assembles the schema.org payload by
   string‑concatenating Smarty variables with `|escape:'javascript'`.
   That escape mode is designed for JavaScript **string literals**, not
   JSON‑inside‑HTML; it neither produces valid JSON escapes nor prevents
   `</script>` breakout. Build the JSON‑LD payload server‑side in
   `item.php`, pass the already‑encoded string to Smarty, and emit it with
   `json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)`.
   Related to [#6](#a-critical--security-vulnerabilities-fix-first) (the same
   rule for conditional‑fields JS) but a separate location.

106. [ ] **HTML attribute values in `<meta>` tags need `|escape:'html'`.**
   `templates/xcreate_item.tpl:6‑21` and `templates/xcreate_index.tpl:5‑16`
   emit `content="<{$seo.title}>"`, `content="<{$seo.description}>"`, etc.
   `item.php:109‑113` pre‑escapes with `htmlspecialchars(..., ENT_QUOTES)`
   before assigning, so today's output is safe — but the contract is
   inconsistent with everywhere else in the module. Pick the "assign raw,
   escape in template" direction (already listed in [#80](#c-medium--architecture-performance-and-maintainability))
   and apply `|escape:'html'` on every meta/OG/Twitter attribute. Otherwise
   a future controller that forgets the pre‑escape quietly opens an
   attribute‑context XSS.

107. [ ] **Explicit layering: domain ⇄ persistence ⇄ presentation.**
   `class/item.php`, `class/field.php`, and `class/fields_helper.php` each
   mix three concerns: domain model (`XcreateItem`/`XcreateField`),
   persistence (`XoopsPersistableObjectHandler` subclass with bespoke SQL),
   and HTML generation (`renderField()`, `buildFields()` emits `<div>`s,
   `<a>` tags, `<img>` tags directly). This is the root cause behind the
   double‑escape issues ([#14](#a-critical--security-vulnerabilities-fix-first),
   [#80](#c-medium--architecture-performance-and-maintainability),
   [#106](#e-added-after-cross-review-with-tasks-codexmd)) and the N+1 queries
   ([#39](#b-high--correctness-and-data-integrity)–[#41](#b-high--correctness-and-data-integrity)).
   Order of refactor: (a) move HTML emission from handlers into
   per‑type view templates or a dedicated `FieldRenderer`, (b) turn
   handlers into narrow repositories returning DTOs, (c) keep
   `XcreateItem`/`XcreateField` as data containers only. This unlocks
   testability (handlers can be mocked) and makes the escape contract
   enforceable.

108. [ ] **Codex ordering hint: repositories before query optimization.**
   Codex #14 / #16 make an important sequencing point — extract the
   repository/query layer ([#56](#c-medium--architecture-performance-and-maintainability))
   **before** adding indexes ([#51](#c-medium--architecture-performance-and-maintainability))
   or fixing N+1 ([#39](#b-high--correctness-and-data-integrity)–[#41](#b-high--correctness-and-data-integrity)).
   Optimizing scattered inline SQL leaves the optimization spread across
   seven files; moving the SQL into one place first means each subsequent
   perf change is one diff, not seven.

109. [ ] **Standardize on the 404‑style direct‑access guard.**
   [#89](#d-low--style-docs-tests) already asks for guards everywhere,
   but doesn't pick a pattern. Choose one and apply it uniformly:
   ```php
   defined('XOOPS_ROOT_PATH') || http_response_code(404) && exit();
   ```
   rather than today's mix of `exit();`, `die('XOOPS root path not defined')`,
   and no guard at all. The 404 variant hides the fact that the file is
   a class library, which is the more defensive default for an internet‑
   facing install.

---

## Codex items already covered

For traceability: Codex items 1–32 each map to one or more items above.

| Codex | Covered by |
|-------|-----------|
| 1 (submit CSRF + allow_user_submit)       | [#1](#a-critical--security-vulnerabilities-fix-first), [#17](#a-critical--security-vulnerabilities-fix-first) |
| 2 (admin GET mutations)                   | [#2](#a-critical--security-vulnerabilities-fix-first) |
| 3 (rating CSRF)                           | [#3](#a-critical--security-vulnerabilities-fix-first) |
| 4 (X‑Forwarded‑For + guest vote identity) | [#4](#a-critical--security-vulnerabilities-fix-first), [#27](#a-critical--security-vulnerabilities-fix-first) |
| 5 (stored HTML policy)                    | [#13](#a-critical--security-vulnerabilities-fix-first), [#100](#d-low--style-docs-tests) |
| 6 (stored XSS in templates)               | [#11](#a-critical--security-vulnerabilities-fix-first) |
| 7 (meta + JSON‑LD rendering)              | **[#105](#e-added-after-cross-review-with-tasks-codexmd)**, **[#106](#e-added-after-cross-review-with-tasks-codexmd)** (net‑new) |
| 8 (search highlight XSS)                  | **[#104](#e-added-after-cross-review-with-tasks-codexmd)** (net‑new) |
| 9 (FieldsHelper URL validation)           | [#14](#a-critical--security-vulnerabilities-fix-first) |
| 10 (runtime schema mutations)             | [#22](#a-critical--security-vulnerabilities-fix-first) |
| 11 (InnoDB + utf8mb4)                     | [#52](#c-medium--architecture-performance-and-maintainability) |
| 12 (duplicate cat_meta_*)                 | [#31](#b-high--correctness-and-data-integrity) |
| 13 (transaction‑wrapped deletes)          | [#28](#a-critical--security-vulnerabilities-fix-first) |
| 14 (repository/query layer)               | [#56](#c-medium--architecture-performance-and-maintainability) + **[#108](#e-added-after-cross-review-with-tasks-codexmd)** (ordering hint) |
| 15 (Xmf\\Request)                          | [#55](#c-medium--architecture-performance-and-maintainability) |
| 16 (criteria/builder SQL)                 | [#56](#c-medium--architecture-performance-and-maintainability) |
| 17 (indexes)                              | [#51](#c-medium--architecture-performance-and-maintainability) |
| 18 (N+1 in list/search)                   | [#39](#b-high--correctness-and-data-integrity), [#40](#b-high--correctness-and-data-integrity), [#41](#b-high--correctness-and-data-integrity) |
| 19 (shared upload service)                | [#76](#c-medium--architecture-performance-and-maintainability) |
| 20 (upload MIME / polyglot)               | [#15](#a-critical--security-vulnerabilities-fix-first) |
| 21 (template filename basename)           | [#7](#a-critical--security-vulnerabilities-fix-first), [#8](#a-critical--security-vulnerabilities-fix-first) |
| 22 (scaffold delimiter bug)               | [#9](#a-critical--security-vulnerabilities-fix-first) |
| 23 (direct‑access guard standardization)  | [#89](#d-low--style-docs-tests) + **[#109](#e-added-after-cross-review-with-tasks-codexmd)** (pattern pick) |
| 24 (silent error suppression)             | [#23](#a-critical--security-vulnerabilities-fix-first) |
| 25 (PSR‑3 logger)                         | [#25](#a-critical--security-vulnerabilities-fix-first) |
| 26 (split oversized controllers)          | [#65](#c-medium--architecture-performance-and-maintainability) |
| 27 (architectural boundaries)             | **[#107](#e-added-after-cross-review-with-tasks-codexmd)** (net‑new) |
| 28 (inline CSS/JS → assets)               | [#63](#c-medium--architecture-performance-and-maintainability) |
| 29 (remove demo templates)                | [#67](#c-medium--architecture-performance-and-maintainability) |
| 30 (search UI logic bug)                  | [#68](#c-medium--architecture-performance-and-maintainability) |
| 31 (CI for PHP 8.2–8.5)                   | [#96](#d-low--style-docs-tests), [#98](#d-low--style-docs-tests) |
| 32 (PHP 8.2+ modernization)               | [#71](#c-medium--architecture-performance-and-maintainability), [#72](#c-medium--architecture-performance-and-maintainability) |

---

## Appendix — Files reviewed

| Area | Files |
|------|-------|
| Entry points | `index.php`, `item.php`, `submit.php`, `search.php`, `xoops_version.php`, `.htaccess` |
| AJAX | `ajax/rating.php`, `ajax/lookup.php`, `ajax/get_cat_fields.php`, `ajax/search_suggest.php` |
| Handlers | `class/item.php`, `class/category.php`, `class/field.php`, `class/rating.php`, `class/group.php`, `class/slug.php`, `class/fields_helper.php`, `class/logger.php` |
| Admin | `admin/index.php`, `admin/items.php`, `admin/categories.php`, `admin/fields.php`, `admin/groups.php`, `admin/import.php`, `admin/export.php`, `admin/menu.php`, `admin/debug_*.php` |
| Blocks / Plugins | `blocks/xcreate_blocks.php`, `blocks/xcreate_filter_block.php`, `blocks/xcreate_widgets.php`, `plugins/function.xcreate.php` |
| Templates | `templates/xcreate_item.tpl`, `xcreate_index.tpl` (others noted for cleanup) |
| SQL | `sql/mysql.sql`, `sql/update_*.sql`, `sql/migrate_from_customfields.sql` |
| Language | `language/english/`, `language/turkish/` |

**Legend**
- [ ] unchecked — pending
- [x] checked — addressed and verified

Fix order suggestion: address section **A** completely before shipping any other changes; sections **B**/**C** can be sequenced by risk and effort; section **D** rolls into the next minor release.
