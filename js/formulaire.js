document.addEventListener("DOMContentLoaded", function () {
  const objetSelect = document.getElementById("objet");
  const personnelContainer = document.getElementById("personnel-container");
  const formationContainer = document.getElementById("formation-container");

  objetSelect.addEventListener("change", function () {
    if (this.value === "personnel") {
      personnelContainer.style.display = "block";
      formationContainer.style.display = "none";
    } else if (this.value === "formation") {
      personnelContainer.style.display = "none";
      formationContainer.style.display = "block";
    } else {
      personnelContainer.style.display = "none";
      formationContainer.style.display = "none";
    }
  });
});
