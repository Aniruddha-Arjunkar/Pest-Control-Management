          
		  $(docment).ready(function(){
            $("input[type='checkbox']").change(function(){
                   if($(this).val()=="other")
            {
                  $("#otherAnswer").show();
             }
        else
           {
              $("#otherAnswer").hide();
           }
  });
  }); 