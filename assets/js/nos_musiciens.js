    // Initialise Rough Notation pour souligner "musiciens"
    document.addEventListener('DOMContentLoaded', function () {
        const musiciens = document.getElementById('highlight-musiciens');
        const musiciensAnnotation = RoughNotation.annotate(musiciens, { type: 'underline', color: '#007bff', strokeWidth: 3 });
        musiciensAnnotation.show();
    });
