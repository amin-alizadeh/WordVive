
$(document).ready(function() {
  $('#profileDetails').form({on: 'blur',
    fields: {
      firstname: {
      identifier  : 'firstname',
      rules: [
        {
        type   : 'minLength[2]',
        prompt : 'First name is too short'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'First name must be maximum 16 characters'
        }
      ]
      },
      lastname: {
      identifier  : 'lastname',
      rules: [
        {
        type   : 'minLength[2]',
        prompt : 'Last name is too short'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'Last name must be maximum 16 characters'
        }
      ]
      },
      nickname: {
      identifier  : 'nickname',
      rules: [
        {
        type   : 'minLength[2]',
        prompt : 'Nickname is too short'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'Nickname must be maximum 16 characters'
        }
      ]
      },
      username: {
      identifier  : 'username',
      rules: [
        {
        type   : 'regExp[/^[a-z0-9_-]{4,16}$/]',
        prompt : 'Please enter a 4-16 letter username'
        }
      ]
      },
      email: {
      identifier  : 'email',
      rules: [
        {
        type   : 'email',
        prompt : 'Please enter a valid e-mail'
        }
      ]
      }
    }
    });
    
    /*
  $('#profilePassword').form({on: 'blur',
    fields: {  
    password: {
      identifier  : 'password',
      rules: [
        {
        type   : 'minLength[6]',
        prompt : 'Password must be 6-16 characters long'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'Password must be 6-16 characters long'
        }
      ]
      },
    newpassword: {
      identifier  : 'newpassword',
      rules: [
        {
        type   : 'minLength[6]',
        prompt : 'Password must be 6-16 characters long'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'Password must be 6-16 characters long'
        }
      ]
      },
      repassword: {
      identifier  : 'repassword',
      rules: [
        {
        type   : 'minLength[6]',
        prompt : 'Password must be 6-16 characters long'
        },
        {
        type   : 'maxLength[16]',
        prompt : 'Password must be 6-16 characters long'
        },
        {
        type   : 'match[newpassword]',
        prompt : 'Passwords do not match'
        }
      ]
      }
    }
    });
    
    */
  $.get("API.php?action=userdetail&token=" + token, function (data) {
		var res = jQuery.parseJSON(data);
    if (res.status == "OK" && res.user.success){
      $('input[name=username]').val(res.user.user.username);
      $('input[name=firstname]').val(res.user.user.firstname);
      $('input[name=lastname]').val(res.user.user.lastname);
      $('input[name=email]').val(res.user.user.email);
      $('input[name=nickname]').val(res.user.user.nickname);
    }		
	});
  
  $("#submitDetails").click(function(){
    if($('#profileDetails').form('is valid')){
      var username = $('input[name=username]').val().trim();
      var firstname = $('input[name=firstname]').val().trim();
      var lastname = $('input[name=lastname]').val().trim();
      var email = $('input[name=email]').val().trim();
      var nickname = $('input[name=nickname]').val().trim();
      
      if (username.length > 0) { 
        
        $('#profileDetails').addClass("loading");
        $("#submitDetails").addClass("loading");
        $.post("API.php?action=updateuserdetail&token=" + token, 
        {firstname:firstname, lastname:lastname, nickname:nickname}, function (data){
          $("#submitDetails").removeClass("loading");
          $('#profileDetails').removeClass("loading");
          
          res = jQuery.parseJSON(data);
          
          if (res.hasOwnProperty("status") && res.status.indexOf("OK") > -1){
            showToast('Success', 'Profile details were successfully updated!', 'success',0)
          } else {
            $('#profileDetails').form ('add errors', ['Error. Try again later']);
            showToast('Error', 'Internal error occured, please try again later!', 'error',0)
          }
        });
      }
    }
  });
  
  $("#submitPassword").click(function(){    
    var password = $('input[name=password]').val().trim();
    var newpassword = $('input[name=newpassword]').val().trim();
    var repassword = $('input[name=repassword]').val().trim();
    var isValid = true;
    var errorMessage = [];
    
    if (password.length < 6 || password.length > 16) { 
      isValid = false;
      errorMessage.push("Current password must be 6 to 16 characters long");
    }
    
    if (newpassword.length < 6 || newpassword.length > 16) { 
      isValid = false;
      errorMessage.push("New password must be 6 to 16 characters long");
    }
    
    
    if (repassword.length < 6 || repassword.length > 16) { 
      isValid = false;
      errorMessage.push("Confirm password must be 6 to 16 characters long");
    }
    
    if (newpassword != repassword) { 
      isValid = false;
      errorMessage.push("New and confirm passwords do not match");
    }
    
    
    if (isValid) { 
      var hashPassword = md5(password);
      var hashNewPassword = md5(newpassword);
      $('#profilePassword').addClass("loading");
      $("#submitPassword").addClass("loading");
      $('#passwordErrorMessage').html('');
      $.post("API.php?action=updatepassword&token=" + token, 
      {password:hashPassword, newpassword:hashNewPassword}, function (data){
        $("#submitPassword").removeClass("loading");
        $('#profilePassword').removeClass("loading");
        
        res = jQuery.parseJSON(data);
        
        if (res.hasOwnProperty("update") && res.update.correctPassword){
          showToast('Success', 'Password successfully updated!', 'success',0);
          $('input[name=password]').val('');
          $('input[name=newpassword]').val('');
          $('input[name=repassword]').val('');
        } else if (res.hasOwnProperty("update") && !res.update.correctPassword){
          showToast('Mismatch', 'The password you entered was incorrect. Try again.', 'warning', 3000)
        } else {
          $('#profilePassword').form ('add errors', ['Error. Try again later']);
          showToast('Error', 'Internal error occured, please try again later!', 'error',0)
        }
      });
    } else {
      $('#profilePassword').form ('add errors', errorMessage);
    }
  });
  
  
/*
var username = $('input[name=username]').val();
var password = $('input[name=password]').val();
var firstname = $('input[name=firstname]').val();
var lastname = $('input[name=lastname]').val();
var email = $('input[name=email]').val();
var terms = $('input[name=terms]').prop('checked');
*/  
});

