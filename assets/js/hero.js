        // Initialise Rough Notation
        document.addEventListener('DOMContentLoaded', function () {
            const musiciens = document.getElementById('musiciens');
            const instruments = document.getElementById('instruments');

            // Crée une annotation sous chaque mot
            const musiciensAnnotation = RoughNotation.annotate(musiciens, { type: 'underline', color: '#007bff', strokeWidth: 3 });
            const instrumentsAnnotation = RoughNotation.annotate(instruments, { type: 'underline', color: '#007bff', strokeWidth: 3 });

            // Affiche l'annotation
            musiciensAnnotation.show();
            instrumentsAnnotation.show();
        });
  