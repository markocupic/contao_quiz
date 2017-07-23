/**
 * Created by Marko on 23.07.2017.
 */

jQuery.fn.contaoQuizStep2 = function (options) {

    // Bob's default settings:
    var defaults = {
        requestToken: '',
        sliderContainer: '.quiz-slider',
        sliderItem: '.quiz-slider-item',
        buttonPrevSlide: '.btn-prev-slide',
        buttonNextSlide: '.btn-next-slide',
        buttonAnswer: '.button-answer'

    };

    var settings = $.extend({}, defaults, options);

    return this.each(function () {
        // Plugin code goes here...
        var quizContainer = $(this);
        var elForm =  $(quizContainer).find('form');



        // Slider Instance
        var sliderInstance = null;


        // Show submit button from the beginning, if there is only one slide
        // Place oninit event before you instantiate the slider
        $(settings.sliderContainer).on('init', function (event, slick) {
            sliderInstance = slick;
            controlNavBtnVisibiliy(slick);

        });

        // Instantiate the slider
        $(settings.sliderContainer).slick({
            slide: settings.sliderItem,
            infinite: false,
            prevArrow: '',
            nextArrow: '',
            adaptiveHeight: true,
            accessibility: false,
            arrows: false,
            draggable: false
        });


        // Event: On after slide change
        $(settings.sliderContainer).on('afterChange', function (event, slick, currentSlide) {
            controlNavBtnVisibiliy(slick);
        });


        // Slider nav-button prev onclick event
        $(settings.buttonPrevSlide).click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(settings.sliderContainer).slick('slickPrev');
        });
        // Slider nav-button next onclick event
        $(settings.buttonNextSlide).click(function (e) {
            e.preventDefault();
            e.stopPropagation();
            $(settings.sliderContainer).slick('slickNext');

        });


        // Answer button handling
        $(settings.buttonAnswer).click(function (e) {
            var elButton = $(this);

            // Remove the buttons if one of them has been clicked
            $(elButton).closest('.question').find('.button-answer').each(function () {
                var elButtons = this;
                window.setTimeout(function(){
                    $(elButtons).remove();
                },300);
            });

            $(elButton).closest(settings.sliderItem).addClass('question-solved');

            var currentSlide = $(settings.sliderContainer).slick('slickCurrentSlide');
            $('.counter-item:eq( ' + (currentSlide) + ' )').addClass('passed');


            // Autosubmit the form after the last question is answered
            if(sliderInstance.currentSlide + 1 == sliderInstance.slideCount)
            {
                window.setTimeout(function(){
                    $(elForm).submit();
                },300);
            }else{
                // Go to the next slide when the answer button was pressed!!!!
                window.setTimeout(function(){
                    $(settings.buttonNextSlide).trigger('click');
                },300);
            }



            // Send answer to server
            if ($(elButton).attr('data-answer') != '') {
                var url = window.location.href + '&send_answer=true';
                var data = {
                    REQUEST_TOKEN: settings.requestToken,
                    data_answer: $(elButton).attr('data-answer')
                };
                var jqxhr = $.post(url, data, function (response) {
                    console.log(response);
                    response = JSON.parse(response);
                    console.log(response);
                });
            }


        });

        /**
         *
         * @param slick
         */
        function controlNavBtnVisibiliy(slick) {
            if (slick.slideCount == slick.currentSlide + 1) {
                $(settings.buttonNextSlide).addClass('disabled');
            } else {
                $(settings.buttonNextSlide).removeClass('disabled');
            }
            if (slick.currentSlide == 0) {
                $(settings.buttonPrevSlide).addClass('disabled');
            } else {
                $(settings.buttonPrevSlide).removeClass('disabled');
            }
        }


    });

};





