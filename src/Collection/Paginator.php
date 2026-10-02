<?php
namespace Clicalmani\Core\Collection;

use Clicalmani\Core\Collection\Collection;

/**
 * Pagination result wrapper.
 *
 * Exposes both the current page's items and pagination metadata. Implements
 * IteratorAggregate and Countable so it can be used as a drop-in replacement
 * for the plain Collection previously returned by DBQuery::paginate().
 *
 * @package Clicalmani\Core
 */
class Paginator implements \IteratorAggregate, \Countable
{
	/**
	 * @param Collection $items Current page items
	 * @param int|null $total Total number of items, or null if not computed
	 * @param int $perPage Items per page
	 * @param int $currentPage Current page number (1-based)
	 */
	public function __construct(
		private Collection $items,
		private ?int $total,
		private int $perPage,
		private int $currentPage,
	) {}

	/**
	 * The items for the current page.
	 */
	public function items() : CollectionInterface
	{
		return $this->items;
	}

	/**
	 * Alias for items(), keeps compatibility with code expecting a Collection.
	 */
	public function result() : CollectionInterface
	{
		return $this->items;
	}

	/**
	 * Total number of items across all pages, or null if not computed.
	 */
	public function total() : ?int
	{
		return $this->total;
	}

	/**
	 * Number of items per page.
	 */
	public function perPage() : int
	{
		return $this->perPage;
	}

	/**
	 * Current page number (1-based).
	 */
	public function currentPage() : int
	{
		return $this->currentPage;
	}

	/**
	 * Last page number, or null if total is unknown.
	 */
	public function lastPage() : ?int
	{
		if (NULL === $this->total) {
			return null;
		}

		return (int) max(1, ceil($this->total / $this->perPage));
	}

	/**
	 * Whether there are more pages after the current one.
	 */
	public function hasMorePages() : bool
	{
		$last = $this->lastPage();

		return NULL === $last ? $this->items->count() === $this->perPage : $this->currentPage < $last;
	}

	/**
	 * Whether the current page is the first one.
	 */
	public function onFirstPage() : bool
	{
		return $this->currentPage === 1;
	}

	/**
	 * Whether the current page is the last one.
	 */
	public function onLastPage() : bool
	{
		$last = $this->lastPage();

		return NULL === $last ? $this->items->count() < $this->perPage : $this->currentPage === $last;
	}

	/**
	 * Index of the first item on the current page (1-based), or 0 if empty.
	 */
	public function firstItem() : int
	{
		return $this->items->count() > 0 ? ($this->currentPage - 1) * $this->perPage + 1 : 0;
	}

	/**
	 * Index of the last item on the current page (1-based), or 0 if empty.
	 */
	public function lastItem() : int
	{
		return $this->items->count() > 0 ? $this->firstItem() + $this->items->count() - 1 : 0;
	}

	/**
	 * Iterate over the current page's items.
	 */
	public function getIterator() : \Traversable
	{
		return $this->items;
	}

	/**
	 * Number of items on the current page.
	 */
	public function count() : int
	{
		return $this->items->count();
	}

	/**
	 * Convert to array: items plus metadata.
	 */
	public function toArray() : array
	{
		return [
			'items'       => $this->items->toArray(),
			'total'       => $this->total,
			'per_page'    => $this->perPage,
			'current_page'=> $this->currentPage,
			'last_page'   => $this->lastPage(),
			'from'        => $this->firstItem(),
			'to'          => $this->lastItem(),
		];
	}
}