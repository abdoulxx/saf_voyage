document.addEventListener('DOMContentLoaded', function () {
    const localisation = document.getElementById('highlight-localisation');
    const annotation = RoughNotation.annotate(localisation, { 
        type: 'underline', 
        color: '#ffcc33', // Couleur jaune
        strokeWidth: 2 
    });
    annotation.show();
});