<?php

namespace HMsoft\Tools\Features\DynamicFilters\Enums;

/**
 * Controls how paginated results are returned from dynamicSearchFromRequest().
 */
enum PaginationFormateEnum: string
{

    case none = 'none';

    case normal = 'normal';

    case separated = 'separated';

    case normal_simple = 'normal_simple';
    case separated_simple = 'separated_simple';
}
