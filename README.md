Doctrine 1
==========

#### [Documentation](https://www.doctrine-project.org/projects/doctrine1/en/latest/index.html) ↗

This is a fork of [doctrine/doctrine1](https://github.com/doctrine/doctrine1).
Requires **PHP 8.3+** and **MySQL 8.0+**. Tested only with MySQL.

It will be maintained as long as we are using it.
Feel free to submit your [Issues](https://github.com/ovos/cms-doctrine/issues) and [Pull Requests](https://github.com/ovos/cms-doctrine/pulls).

There are also some performance tweaks and features added, i.a.:
- **[BC BREAK]** modified doctrine collection & record serialization - store less data in cache, but losing the feature of keeping state of modified data
- **[BC BREAK]** fixed orderBy handling in relations - for ordering m2m relations by columns in `refClass` use `refOrderBy`!
- refactored link/unlink methods in Doctrine_Record - now they do not load whole relations before linking/unlinking
- added `postRelatedSave` hook in Record to be called on save, after all relations are also saved (original postSave method is called before any relation is saved)
- Added `Doctrine_Query_Abstract::getDqlWithParams` - returns the DQL query that is represented by this query object, with interpolated param values, and modified Doctrine_Connection to use PDO::quote for quoting string whenever possible
- queryCache reworked:
  - hook it in getSqlQuery method instead of execute method only (better cache usage)
  - added rootAlias, sqlParts (without offset or limit), isLimitSubqueryUsed and limitSubquery to cache
  - always prequery the query in getSqlQuery to call dql callbacks before any sql is generated, not only on execute(), so that cache hash is always calculated properly, and that this method always returns actual end-query incl. any modifications from dql callbacks
  - added `isQueryCacheEnabled()` method
  - cache queries without limit and offset (to save less cache records) - added `Doctrine_Core::ATTR_QUERY_CACHE_NO_OFFSET_LIMIT` - set to true to enable the feature
  - added parent query components for subqueries, to indicate subquery context - changing cache hash
  - WHERE IN adjustments for better caching and performance
- Limit subquery adjustments and smart LEFT JOIN pruning for performance
- MySQL 8.0+ `ROW_NUMBER()` window function for limit subquery deduplication (replaces deprecated `@rownum` user variables)
- Memory usage improvements:
  - `Doctrine_Query::free()` — comprehensive cleanup of 20+ properties with proper reference-breaking for properties shared between parent/subqueries via `copySubqueryInfo()`
  - `Doctrine_Record::free()` — clears pending deletes/unlinks/links, modified tracking, old values, and mapped values
  - `Doctrine_Record::resetPendingUnlinks()` — also clears `_pendingDeletes` (was a memory leak — never cleared after save)
  - `Doctrine_Collection::free()` — clears snapshot data used by `processDiff()`
- `Doctrine_Table::makeRecordInstance()` — protected factory method for DI-compatible record instantiation, used by `create()` and `getRecord()`
- `Doctrine_Table::hasLoadedRecord()` / `getLoadedRecord()` — identity map helpers to check/retrieve records without triggering a query
- `Doctrine_Record::relatedExists()` — returns `true` for collections instead of throwing an exception
- `Doctrine_Record::__call()` — caches template method owner even when a non-`BadMethodCallException` is thrown (avoids repeated lookups on subsequent calls)
- `Doctrine_Connection_Module` — supports namespaced subclass names (e.g. `CMS\Doctrine\Formatter` resolves module name correctly)

These are only highlights, [full changelog here](CHANGELOG.md).


### Installation
```
composer require ovos/cms-doctrine
```
