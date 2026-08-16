document.addEventListener('DOMContentLoaded', function() {
    const filterCropType = document.getElementById('filterCropType');
    const filterStatus = document.getElementById('filterStatus');
    const filterYear = document.getElementById('filterYear');
    const table = document.querySelector('.crop-requests-page table');

    if (!table || !filterCropType || !filterStatus || !filterYear) {
        return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr'));

    function filterTable() {
        const cropTypeValue = filterCropType.value.toLowerCase();
        const statusValue = filterStatus.value.toLowerCase();
        const yearValue = filterYear.value;

        rows.forEach(row => {
            let show = true;

            if (cropTypeValue) {
                const cropTypeCell = row.cells[3];
                if (cropTypeCell && !cropTypeCell.textContent.toLowerCase().includes(cropTypeValue)) {
                    show = false;
                }
            }

            if (statusValue) {
                const statusCell = row.cells[6];
                if (statusCell) {
                    const statusText = statusCell.textContent.trim().toLowerCase();
                    if (statusText !== statusValue) {
                        show = false;
                    }
                }
            }

            if (yearValue) {
                const submittedCell = row.cells[1];
                if (submittedCell) {
                    const dateText = submittedCell.textContent.trim();
                    try {
                        const dateParts = dateText.split(' ');
                        if (dateParts.length >= 3) {
                            const year = parseInt(dateParts[2].replace(',', ''), 10);
                            if (year.toString() !== yearValue) {
                                show = false;
                            }
                        }
                    } catch (error) {
                        // Keep current visibility state when date parsing fails.
                    }
                }
            }

            row.style.display = show ? '' : 'none';
        });
    }

    filterCropType.addEventListener('change', filterTable);
    filterStatus.addEventListener('change', filterTable);
    filterYear.addEventListener('change', filterTable);

    const headerCheckbox = table.querySelector('thead input[type="checkbox"]');
    const rowCheckboxes = table.querySelectorAll('tbody input[type="checkbox"]');

    if (headerCheckbox && rowCheckboxes.length > 0) {
        headerCheckbox.addEventListener('change', function() {
            rowCheckboxes.forEach(cb => {
                cb.checked = headerCheckbox.checked;
            });
        });
    }
});
