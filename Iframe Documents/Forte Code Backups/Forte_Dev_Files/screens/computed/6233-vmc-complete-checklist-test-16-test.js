(function () {

  function fixLoopRadioNames() {
    // Cada conjunto de radios está envuelto en este div:
    const groups = document.querySelectorAll(
      'div[aria-label=""]'
    );

    groups.forEach(function (group, index) {
      // Todos los radios que hoy comparten el mismo name
      const radios = group.querySelectorAll(
        'input[type="radio"][name="status"]'
      );

      if (!radios.length) {
        return;
      }

      // Hacemos que cada fila tenga su propio grupo:
      // status_0, status_1, ...
      const newName = 'status_' + index;

      radios.forEach(function (radio) {
        // No tocamos checked, solo cambiamos el name
        radio.name = newName;
      });
    });
  }

  // Cuando la pantalla termina de cargar
  window.addEventListener('load', function () {
    // Pequeño delay para asegurar que Vue ya pintó todo
    setTimeout(fixLoopRadioNames, 0);
  });

  // Por si el loop se re-renderiza (agregar/eliminar filas)
  const renderer = document.getElementById('vue-form-renderer');
  if (renderer) {
    const observer = new MutationObserver(function () {
      fixLoopRadioNames();
    });

    observer.observe(renderer, {
      childList: true,
      subtree: true
    });
  }

})();