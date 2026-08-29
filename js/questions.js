(function(){
    const titleQue = [...document.querySelectorAll('.questions_title')];
    console.log(titleQue);
    
    titleQue.forEach(question=>{
        question.addEventListener('click',()=>{
           //alert('me diste');
            let height = 0;
            let answer = question.nextElementSibling;
            let addPadding = question.parentElement.parentElement;

            addPadding.classList.toggle('questions_padding--add');

            question.children[0].classList.toggle('questions_arrow--rotate');

            if(answer.clientHeight === 0){
                height = answer.scrollHeight;
            }
            answer.style.height = `${height}px`;
        });
    });
})();