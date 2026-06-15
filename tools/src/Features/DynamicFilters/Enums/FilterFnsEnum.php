<?php

namespace HMsoft\Tools\Features\DynamicFilters\Enums;

use ArchTech\Enums\Names;
use ArchTech\Enums\From;

enum FilterFnsEnum: string
{
    use From, Names;

    case between = 'between';
    case betweenInclusive = 'betweenInclusive';
    case contains = 'contains';
    case isNull = 'isNull';
    case notIsNull = 'notIsNull';
    case empty = 'empty';
    case endsWith = 'endsWith';
    case equals = 'equals';
    case fuzzy = 'fuzzy';
    case greaterThan = 'greaterThan';
    case greaterThanOrEqualTo = 'greaterThanOrEqualTo';
    case lessThan = 'lessThan';
    case lessThanOrEqualTo = 'lessThanOrEqualTo';
    case notEmpty = 'notEmpty';
    case notEquals = 'notEquals';
    case startsWith = 'startsWith';
    case includesString = 'includesString';
    case includesStringSensitive = 'includesStringSensitive';
    case equalsString = 'equalsString';
    case arrIncludes = 'arrIncludes';
    case arrIncludesAll = 'arrIncludesAll';
    case arrIncludesSome = 'arrIncludesSome';
    case weakEquals = 'weakEquals';
    case inNumberRange = 'inNumberRange';

    case dayEquals = 'dayEquals';

    case in = 'in';
    case notIn = 'notIn';
    case notContains = 'notContains';
    case notStartsWith = 'notStartsWith';
    case notEndsWith = 'notEndsWith';
}
