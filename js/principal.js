(function(){
    const prin = [...document.querySelectorAll('.nosotros_body')];
    const buttonNext = document.querySelector('#next');
    const buttonBefore = document.querySelector('#before');
    
    let values;

    buttonNext.addEventListener('click', ()=>{
        changePosition(1);
    });
    buttonBefore.addEventListener('click', ()=>{
        changePosition(-1);
    });
    const changePosition = (add)=>{
        const currentNosotros = document.querySelector('.nosotros_body--show').dataset.id;
        values = Number(currentNosotros);
        values += add;
        console.log(values);
        /*console.log(currentNosotros);*/

        prin[Number(currentNosotros)-1].classList.remove('nosotros_body--show');
        if(values === prin.length+1 || values === 0){
            values = values === 0 ? prin.length : 1;
        }
        prin[values-1].classList.add('nosotros_body--show');
    }
})();