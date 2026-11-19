$(document).ready(function(){

  // Search all columns
  $('#txt_searchall').keyup(function(){
    // Search Text
    var search = $(this).val();

    // Hide all table tbody rows
    $('table tbody tr').hide();

    // Count total search result
    var len = $('table tbody tr:not(.notfound) td:contains("'+search+'")').length;

    if(len > 0){
      // Searching text in columns and show match row
      $('table tbody tr:not(.notfound) td:contains("'+search+'")').each(function(){
        $(this).closest('tr').show();
      });
    }else{
      $('.notfound').show();
    }

  });

  // Search on name column only
  $('#txt_name').keyup(function(){
    // Search Text
    var search = $(this).val();

    // Hide all table tbody rows
    $('table tbody tr').hide();

    // Count total search result
    var len = $('table tbody tr:not(.notfound) td:nth-child(2):contains("'+search+'")').length;

    if(len > 0){
      // Searching text in columns and show match row
      $('table tbody tr:not(.notfound) td:contains("'+search+'")').each(function(){
         $(this).closest('tr').show();
      });
    }else{
      $('.notfound').show();
    }

  });

});

// Case-insensitive searching (Note - remove the below script for Case sensitive search )
$.expr[":"].contains = $.expr.createPseudo(function(arg) {
   return function( elem ) {
     return $(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;
   };
});













// ===============================
// TOP SEARCH BAR (START)
// ===============================

// ===============================
// GLOBAL STATE
// ===============================
window.searchTimeout = null;
window.searchActive = false;
window.lastSearch = "";


// ===============================
// MAIN SEARCH
// ===============================
function searchSidebar(event) {

    let inputEl = document.getElementById("sidebarSearch");
    let resultBox = document.getElementById("searchResults");

    if (!inputEl) return;

    let input = inputEl.value.trim();

    clearTimeout(window.searchTimeout);

    // ===============================
    // RESET
    // ===============================
    if (input === "") {

        window.searchActive = false;
        window.lastSearch = "";

        if (resultBox) resultBox.style.display = "none";

        resetSidebarMenu();
        resetTableOnly();

        return;
    }

    // ===============================
    // SET ACTIVE STATE
    // ===============================
    window.searchActive = true;
    window.lastSearch = input.toLowerCase();

    // ✔ KEEP BOTH WORKING
    applySidebarFilter(window.lastSearch);
    applyTableFilter();

    // ===============================
    // AJAX CLIENT SEARCH
    // ===============================
    window.searchTimeout = setTimeout(() => {

        fetch("global-search.php?query=" + encodeURIComponent(input))
            .then(res => res.json())
            .then(data => {

                if (!Array.isArray(data) || data.length === 0) {
                    if (resultBox) resultBox.style.display = "none";
                    return;
                }

                resultBox.innerHTML = data.map(item => `
                    <div class="global-search-item"
                         onclick="window.location.href='${item.url}'">
                        <div><strong>${item.name}</strong></div>
                        <div>${item.location || ""}</div>
                    </div>
                `).join("");

                resultBox.style.display = "block";
            });

    }, 200);
}


// ===============================
// TABLE FILTER (SAFE)
// ===============================
function applyTableFilter() {

    let rows = document.querySelectorAll("table tbody tr");

    rows.forEach(row => {

        if (!window.searchActive) {
            row.style.display = "";
            return;
        }

        let text = row.innerText.toLowerCase();

        row.style.display =
            text.includes(window.lastSearch) ? "" : "none";
    });
}


// ===============================
// RESET TABLE
// ===============================
function resetTableOnly() {

    document.querySelectorAll("table tbody tr").forEach(row => {
        row.style.display = "";
    });
}


// ===============================
// SIDEBAR FILTER (FIXED - SAFE VERSION)
// ===============================
function applySidebarFilter(input) {

    let allLinks = document.querySelectorAll("#nav-accordion a");

    let matched = false;

    document.querySelectorAll("#nav-accordion li").forEach(li => {
        li.style.display = "none";
    });

    allLinks.forEach(link => {

        let text = link.textContent.toLowerCase();
        let li = link.closest("li");

        if (text.includes(input)) {

            matched = true;

            li.style.display = "";

            let parent = li;

            while (parent) {

                parent.style.display = "";

                let subUl = parent.querySelector(":scope > ul.sub");
                if (subUl) subUl.style.display = "block";

                parent = parent.parentElement.closest("li");
            }
        }
    });

    // ✔ FIX: if nothing matches, restore menu
    if (!matched) {
        resetSidebarMenu();
    }
}


// ===============================
// RESET SIDEBAR
// ===============================
function resetSidebarMenu() {

    document.querySelectorAll("#nav-accordion li").forEach(li => {
        li.style.display = "";
        li.classList.remove("open");
    });

    document.querySelectorAll("#nav-accordion ul.sub").forEach(ul => {
        ul.style.display = "";
    });
}


// ===============================
// MUTATION OBSERVER
// ===============================
const observer = new MutationObserver(() => {

    if (window.searchActive) {
        applyTableFilter();
    }
});

observer.observe(document.body, {
    childList: true,
    subtree: true
});


// ===============================
console.log("SEARCH SYSTEM FULLY FIXED AND STABLE");
// ===============================
// TOP SEARCH BAR (END)
// ===============================