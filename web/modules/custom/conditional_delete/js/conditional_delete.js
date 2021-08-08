(Drupal => {
  Drupal.behaviors.conditionalDeleteFiltersTable = {
    attach: function (context) {
      const filtersTable = context.querySelector("#filters-table-wrapper table");
      if (!filtersTable) {
        return;
      }

      const rowRemoveButton = context.querySelector("input[data-drupal-selector=edit-remove-filters-table-row]");
      if (!rowRemoveButton) {
        return;
      }

      filtersTable.querySelectorAll(".remove-filters-table-row").forEach(button => {
        button.addEventListener("click", e => {
          e.preventDefault();

          const rowCheckbox = button.closest("tr").querySelector("input[type=checkbox]");
          if (rowCheckbox) {
            rowCheckbox.checked = true;
            rowRemoveButton.dispatchEvent(new Event('mousedown'));
          }
        });
      });
    },
  };

})(Drupal);
