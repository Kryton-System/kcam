// Copy each column title onto its cells so stacked tables (phones) can label every value.
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('table[data-stack]').forEach((table) => {
        const titles = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());

        table.querySelectorAll('tbody tr').forEach((row) => {
            [...row.children].forEach((cell, index) => {
                if (titles[index] && !cell.hasAttribute('data-label')) {
                    cell.setAttribute('data-label', titles[index]);
                }
            });
        });
    });
});
