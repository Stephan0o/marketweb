<!DOCTYPE html>
<html lang="es">
<head>
    <link rel="icon" type="image/png" href="../../public/assets/img/logo.png">
    <meta charset="UTF-8">
    <title>Generando Estrategias...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../public/assets/css/custom.css">
    <style>
        #loadingScreen {
            position: fixed;
            inset: 0;
            background: var(--gradient);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            font-family: var(--font);
            z-index: 9999;
        }
        .spinner-border {
            width: 4rem;
            height: 4rem;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div id="loadingScreen">
        <div class="spinner-border text-light" role="status"></div>
        <h3>Generando tus estrategias para la campaña...</h3>
        <p>Por favor espera unos segundos ⏳</p>
    </div>

    <script>
        // Obtener el ID de campaña desde la URL
        const urlParams = new URLSearchParams(window.location.search);
        const campaniaId = urlParams.get('campania_id');

        if (!campaniaId) {
            alert('ID de campaña no proporcionado');
            window.location.href = '../../public/estrategias.php';
        }

        // Hacer la petición al backend
        fetch(`generar_estrategias_api.php?campania_id=${campaniaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Redirigir a la página de resultados
                    window.location.href = `../../public/ver_estrategias.php?campania_id=${campaniaId}`;
                } else {
                    alert('Error: ' + data.message);
                    window.location.href = '../../public/estrategias.php';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al generar las estrategias');
                window.location.href = '../../public/estrategias.php';
            });
    </script>
</body>
</html>