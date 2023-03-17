$(document).ready(function(){
    $(".hover").hover(function(){
        var classList = $(this).attr('class').split(/\s+/);
        $.each(classList, function(index, item) {
            if (item.startsWith("a")) {
                newitem = item.replace("a", "b");
                $("."+newitem).css("background-color", "aliceblue");
            }
            else if (item.startsWith("b")){
                newitem = item.replace("b", "a");
                $("."+newitem).css("background-color", "aliceblue");
            }
        });
    },
    function(){
        var classList = $(this).attr('class').split(/\s+/);
        $.each(classList, function(index, item) {
            if (item.startsWith("a")) {
                newitem = item.replace("a", "b");
                $("."+newitem).css("background-color", "white");
            }
            else if (item.startsWith("b")){
                newitem = item.replace("b", "a");
                $("."+newitem).css("background-color", "white");
            }
        });
    });

    $('#newGpuButton').click(function(){
        $('#addGpuWindow').toggleClass('showNewGpuWindow');
    });

    $('#closeNewGpuWindow').click(function(){
        $('#addGpuWindow').toggleClass('showNewGpuWindow');
    });

    $('#removeGpuButton').click(function(){
        $('#removeGpuWindow').toggleClass('showRemoveGpuWindow');
    });

    $('#closeRemoveGpuWindow').click(function(){
        $('#removeGpuWindow').toggleClass('showRemoveGpuWindow');
    });

    // Show / Hide GPU price trend iframes
    $('.priceUnit').on('click', function(e) {
        e.preventDefault();
        var idList = $(this).attr('id');
        $('#'+idList+'Page').css( 'top', e.pageY+10);
        $('#'+idList+'Page').css( 'left', e.pageX+10 );
        //$('#'+idList+'Page').toggleClass('activePriceDisplay');
        $('#'+idList+'Page').toggleClass('hidePriceDisplay');
    });

    // Show / Hide GPU price trend iframes
    $('.gpuNameDictButton').on('click', function(e) {
        $('.gpuNameDict').toggleClass('hidden');
    });
});