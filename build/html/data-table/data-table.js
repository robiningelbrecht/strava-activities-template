const initDataTables = async (callbackFn) => {
    const dataTables = document.querySelectorAll('div[data-dataTable-settings]');

    dataTables.forEach(function (dataTableWrapperNode) {
        const settings = JSON.parse(dataTableWrapperNode.getAttribute('data-dataTable-settings'));

        const searchInput = dataTableWrapperNode.querySelector('input[type="search"]');
        const dataTable = dataTableWrapperNode.querySelector('table');

        if (!searchInput) {
            return;
        }
        if (!dataTable) {
            return;
        }

        fetch(settings.url).then(async function (response) {
            const dataRows = await response.json();

            const clusterize = new Clusterize({
                rows: filterDataRows(dataRows),
                scrollElem: dataTableWrapperNode.querySelector('.scroll-area'),
                contentElem: dataTable.querySelector('tbody'),
                no_data_class: 'clusterize-loading',
                callbacks:{
                    clusterChanged: ()=> {
                        callbackFn();
                    }
                }
            });

            let sortOnPrevious = null;
            let sortAsc = false;
            const sortableColumns = dataTable.querySelectorAll('thead tr th[data-dataTable-sort]');
            sortableColumns.forEach(element => {
                element.addEventListener('click', ()=> {
                    const sortOn = element.getAttribute('data-dataTable-sort');
                    if(sortOn === sortOnPrevious){
                        sortAsc = !sortAsc;
                    }
                    sortOnPrevious = sortOn;
                    // Highlight sorting icons.
                    sortableColumns.forEach(el=>el.querySelector('.sorting-icon').setAttribute('aria-sort', 'none'))
                    element.querySelector('.sorting-icon').setAttribute('aria-sort', sortAsc ? 'ascending' : 'descending');
                    // Do the actual sort.
                    dataRows.sort((a, b) => {
                        if (a.sort[sortOn] < b.sort[sortOn]) return sortAsc ? -1 : 1;
                        if (a.sort[sortOn] > b.sort[sortOn]) return sortAsc ? 1 : -1;
                        return 0;
                    });
                    // Update the rows.
                    clusterize.update(filterDataRows(dataRows));
                });
            });

            searchInput.addEventListener('keyup', e => {
                const search = e.target.value.toLowerCase();
                for (let i = 0, ii = dataRows.length; i < ii; i++) {
                    const searchables = dataRows[i].searchables.toLowerCase();
                    dataRows[i].active = !(searchables.indexOf(search) === -1);
                }
                clusterize.update(filterDataRows(dataRows));
            });
        });
    });
};

const filterDataRows = function (rows) {
    return rows.filter((row) => row.active).map((row) => row.markup);
}