(($) => {
	$(document).ready(()=>{
        animateJederKommen()
        animateFadeInMaskText()
        $(document).on('nfFormReady', function() {
            contactFormLabel()
        });
    })

    /*Kontakt-Formular Label-Activation*/
    function contactFormLabel(){
        let labelWrap = 'form ';
        let input     = labelWrap + '.nf-field-element input';
        let textarea  = labelWrap + '.nf-field-element textarea';
        let fields    = input + ", " + textarea;
        
        function checkInputField() {
            $(fields).each(function(){
                if($(this).val() == ""){
                    $(this).parents(':eq(1)').removeClass('focus-input');
                }
            })
        }

        $(fields).on('focus click', function(){
            checkInputField();
        })

        $(fields).on('focus click', function(){
            $(this).parents(':eq(1)').addClass('focus-input');
        })

        $(fields).on('focusout', function(){
            checkInputField()
        })
    }

    function animateJederKommen(){
        let wrapper = '.jeder-kommen p.has-xx-large-font-size'
        let elements = document.querySelectorAll(wrapper)
        elements.forEach((el) => { 
            gsap.from(el, {
            filter: "blur(20px)",
            scale: 1.2,
            opacity:0,
            ease: "none",
            scrollTrigger: {
                trigger: el,
                start: "top 50%",
                end: "top 40%",
                scrub: true,
            }
            });
        })
    }

    function animateFadeInMaskText(){
        let elements = document.querySelectorAll('p.fadinmask')
        elements.forEach((el) => { 
           ScrollTrigger.create({
                trigger: el,
                start: "top 70%",
                markers: false,
                onEnter: () => {
                gsap.to(el, {
                    "--y": "100%",
                    ease: "none",
                    duration: 0.5,
                    y:-10
                });
                },
                // optional:
                onLeaveBack: () => {
                  gsap.to(el, {
                     "--y": "0%", 
                     duration: 0.5,
                     y:0
                     });
                }
            });
        })
    }
})(jQuery)