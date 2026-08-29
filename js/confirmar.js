/**
 * Modal de confirmación con SweetAlert2, con los colores de la
 * universidad, para usar en vez del confirm() feo del navegador.
 *
 * Uso en el HTML:
 * <a href="#" onclick="confirmarAccion('eliminar.php?id=5', '¿Seguro que quieres borrar esto?'); return false;">🗑️</a>
 */
function confirmarAccion(url, mensaje = '¿Estás seguro?', textoBoton = 'Sí, continuar') {
    Swal.fire({
        title: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: textoBoton,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#EF3E36',
        cancelButtonColor: '#5B2C8C',
        background: '#ffffff',
        customClass: {
            popup: 'swal-edutrivia'
        }
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

/**
 * Igual que arriba, pero para cuando la acción a confirmar es el
 * ENVÍO de un formulario (en vez de solo ir a un link).
 *
 * Uso en el HTML:
 * <form id="miForm" method="POST" ...>
 * <button type="button" onclick="confirmarFormulario('miForm', '¿Seguro?')">Enviar</button>
 */
function confirmarFormulario(idFormulario, mensaje = '¿Estás seguro?', textoBoton = 'Sí, continuar') {
    Swal.fire({
        title: mensaje,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: textoBoton,
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#EF3E36',
        cancelButtonColor: '#5B2C8C',
    }).then((resultado) => {
        if (resultado.isConfirmed) {
            document.getElementById(idFormulario).submit();
        }
    });
}

/**
 * Aviso simple (sin botón de cancelar), para reemplazar los alert()
 * feos también. Uso: avisar('Bienvenido!');
 */
function avisar(mensaje, icono = 'success') {
    Swal.fire({
        title: mensaje,
        icon: icono,
        confirmButtonText: 'Entendido',
        confirmButtonColor: '#5B2C8C',
    });
}