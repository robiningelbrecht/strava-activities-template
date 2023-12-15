const initSearchableTables = () => {
    const searchInput = document.querySelector('input.table-search');
    const searchableTable = document.querySelector('table.searchable');
    if (!searchInput) {
        return;
    }
    if (!searchableTable) {
        return;
    }
    searchInput.addEventListener('keyup', e => {
        const search = e.target.value.toLowerCase();
        for (const tbody of searchableTable.tBodies) {
            for (const row of tbody.rows) {
                const searchables = row.getAttribute('data-searchables').toLowerCase();
                row.style.display = searchables.indexOf(search) === -1 ? 'none' : 'table-row';
            }
        }
    });
};
