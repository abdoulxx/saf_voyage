    // Initialise Rough Notation pour souligner "instruments"
    document.addEventListener('DOMContentLoaded', function () {
        const instruments = document.getElementById('highlight-instruments');
        const instrumentsAnnotation = RoughNotation.annotate(instruments, { type: 'underline', color: '#ffcc33', strokeWidth: 3 });
        instrumentsAnnotation.show();
    });
