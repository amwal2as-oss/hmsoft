# SortNumber Feature

The Sort Number feature automatically assigns an incremental sorting value to your Eloquent models upon creation. It also shifts later rows when a save lands on an occupied rank.

## Directory Structure

```text
HMsoft/Tools/Features/SortNumber/
├── Contracts/
│   └── Sortable.php
├── Support/
│   └── SortNumberShifter.php
├── Traits/
│   └── HasSortNumber.php
├── Providers/
│   └── SortNumberServiceProvider.php
└── config/
    └── sort_number.php
```

## Installation & Usage

Your model must implement the Sortable contract and use the HasSortNumber trait.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;

class Category extends Model implements Sortable
{
    use HasSortNumber;

    protected $fillable = ['name', 'sort_number'];
}
```

When you create a new Category with an empty sort value, `sort_number` is `MAX(sort_number) + 1`.

When you save onto a rank that is already used, later rows in the **same sort context** are bumped by `+1` (query builder, no model events). Nested lists stay isolated with:

```php
public const SORT_NUMBER_CONTEXT = ['parent_id'];
```

`0` / `null` is not a rank. Empty values still get `MAX + 1` on create.

## Collision shift config

Package default is on. Publish (optional) and/or set env:

```
php artisan vendor:publish --tag=hmsoft-sort-number-config
```

```
SORT_NUMBER_SHIFT_ON_COLLISION=false
```

Then `php artisan config:clear` (or `config:cache` in production).

Query-builder / raw SQL / `sync()` updates do not run the shifter. Mass-patch that calls `$row->save()` does.

## Customization

### Changing the column name

By default the trait looks for `sort_number`. If your table uses a different column (e.g. `order_index`), customize it in one of three ways:

1. Using a constant (recommended):

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    const SORT_COLUMN = 'order_index';
}
```

2. Using a class property:

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    public $sortNumberColumn = 'order_index';
}
```

3. Overriding the contract method:

```php
class Category extends Model implements Sortable
{
    use HasSortNumber;

    public function getSortNumberColumnName(): string
    {
        return 'order_index';
    }
}
```

### Overriding context scoping (`scopeSortByContext`)

When working with complex relational layers (such as polymorphic models), sorting should be calculated within explicit contexts (e.g. `owner_id` and `owner_type`).

You can override `scopeSortByContext` to declare your own index boundaries:

```php
<?php

namespace App\Features\Faq\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use HMsoft\Tools\Features\SortNumber\Contracts\Sortable;
use HMsoft\Tools\Features\SortNumber\Traits\HasSortNumber;

class Faq extends Model implements Sortable
{
    use HasSortNumber;

    protected $fillable = ['owner_id', 'owner_type', 'sort_number'];

    public function scopeSortByContext(Builder $query): Builder
    {
        $ownerType = $this->getAttribute('owner_type');
        $ownerId = $this->getAttribute('owner_id');

        if ($ownerType && $ownerId) {
            return $query->where('owner_type', $ownerType)->where('owner_id', $ownerId);
        } elseif ($ownerType && !$ownerId) {
            return $query->where('owner_type', $ownerType)->whereNull('owner_id');
        }

        return $query->whereNull('owner_type')->whereNull('owner_id');
    }
}
```

## How it works

The trait hooks into Eloquent `creating` and `saving`:

1. On create, if the sort column is `null` or `0`, it sets `MAX + 1` in the current sort context.
2. On save, if that rank is already occupied, `SortNumberShifter` increments every later row in the same context, then the saved row keeps the requested number.
