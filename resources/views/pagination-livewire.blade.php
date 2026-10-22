{{--
    ds::pagination for a Livewire component using WithPagination: buttons that
    call gotoPage / previousPage / nextPage instead of links that reload the
    page. Return 'ds::pagination-livewire' from the component's
    paginationView(). See docs/components/pagination.md.
--}}
@include('ds::pagination', ['livewire' => true])
