document.addEventListener('DOMContentLoaded', function () {
    const temoignages = document.getElementById('highlight-temoignages');
    const annotation = RoughNotation.annotate(temoignages, { 
        type: 'underline', 
        color: '#007bff', // Couleur bleue
        strokeWidth: 2 
    });
    annotation.show();
});