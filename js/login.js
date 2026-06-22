document.addEventListener("DOMContentLoaded", function() {
    const loginForm = document.querySelector("form"); 
    
    if (loginForm) {
        loginForm.addEventListener("submit", function(e) {
            e.preventDefault();
            
            fetch('login.php', {  // Apunta directo a la raíz
                method: 'POST',
                body: new FormData(loginForm)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.redirect; // Te deja pasar al panel
                } else {
                    alert(data.message); // Te avisa si la clave o RUT están mal
                }
            })
            .catch(err => {
                alert("Error de red conectando con AWS.");
            });
        });
    }
});