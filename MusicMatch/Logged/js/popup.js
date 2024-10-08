// Contenuto di scripts.js
document.addEventListener('DOMContentLoaded', function() {
    var modal = document.getElementById("modal");
    var closeBtn = document.getElementsByClassName("close")[0];

    // Mostra il modal se c'è il parametro 'view_details' nell'URL
    if (new URLSearchParams(window.location.search).has('view_details')) {
        if (modal) {
            modal.style.display = "block";
        }
    }

    // Chiudi il modal quando clicchi sulla "x" di chiusura
    closeBtn.onclick = function() {
        modal.style.display = "none";
        // Rimuovi il parametro 'view_details' dall'URL
        var url = new URL(window.location.href);
        url.searchParams.delete('view_details');
        window.history.pushState({}, '', url);
    }

    // Chiudi il modal quando clicchi al di fuori del modal
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
            // Rimuovi il parametro 'view_details' dall'URL
            var url = new URL(window.location.href);
            url.searchParams.delete('view_details');
            window.history.pushState({}, '', url);
        }
    }
});
