const btnSignIn = document.getElementById("sign-in"),
      btnSignUp = document.getElementById("sign-up"),
      formRegister = document.querySelector(".register"),
      formLogin = document.querySelector(".login");

btnSignIn.addEventListener("click",e=>{
    formRegister.classList.add("hide");
    formLogin.classList.remove("hide");
})
btnSignUp.addEventListener("click",e=>{
    formLogin.classList.add("hide");
    formRegister.classList.remove("hide");
})

// Detectar si se solicitó el modo registro por URL (?modo=registro)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('modo') === 'registro') {
        const formRegistro = document.getElementById('formRegistro');
        const formLoginBox = document.getElementById('formLoginBox');
        
        if (formRegistro && formLoginBox) {
            formRegistro.classList.remove('hide');
            formLoginBox.classList.add('hide');
        }
    }