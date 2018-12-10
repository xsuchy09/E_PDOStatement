## Change Log

### 3.0.0 (2018-12-10)
#### Added
- PHP 7.2, 7.3 support. For lower PHP versions use version 2.2.2.

### 2.2.2 (2017-10-12)
#### Fixed
- testParam regular changed - so fulltext search is now supported.

### 2.2.1 (2017-10-10)
First version from Ing. Petr Suchý (xsuchy09)
#### Fixed
- When marker is word then can replaced more times.
#### Edited
- Composer.
- Readme.

### 2.2.0 (2016-11-08)

#### Added

- Updated parameter interpolation process for more reliable / proper behavior
- Handle null values

### 2.1.5 (2016-05-13)

#### Added

- Test for successful execution of database query with named placeholders
- Updated README

#### Fixed

- Remove un-needed escape sequences in regular expression patterns

### 2.1.4 (2015-10-25)

####Added

- Nothing

#### Fixed

- Remove duplication of code for input and bound parameters
- Identified and fixed documentation errors

### 2.1.3 (2015-10-24)

####Added

- Full PHPUnit Test Suite
- Reorganize code to more suitable project structure

### 2.1.2 (2015-07-19)

####Added

- Nothing

####Fixed

- Now takes into account bound arguments' datatypes when compiling interpolated string (previously, all values were quoted when it's likely inappropriate to quote INT datatypes). This allows for viewing/using bound values in e.g. LIMIT clauses where the quotes would interfere with processing the resultant query.
