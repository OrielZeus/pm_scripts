window.addEventListener('load', function () {
    // Cada fila del checklist tiene este wrapper:
    const groups = document.querySelectorAll('[selector="checkItemRadio2"]');

    groups.forEach(function (group, index) {
        // Todos los radios que hoy comparten name="status"
        const radios = group.querySelectorAll(
            'input[type="radio"][name="status"]'
        );

        radios.forEach(function (radio) {
            // Hacemos que cada fila tenga su propio grupo:
            // status_0, status_1, status_2, ...
            radio.name = 'status_' + index;
        });
    });
});