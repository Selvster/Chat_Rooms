$(document).ready(function() {
  //Helper Functions
  //Check if cookie is set
  function CheckCookie(name) {
    if ($.cookie(name)) {
      return true;
    }
    return false;
  }
  //Check if username is registered before or not
  function checkUsername(username) {
    return $.ajax({
      type: "POST",
      url: "ajax/check_username.php",
      data: { username: username },
      async: false
    }).responseText;
  }
  //Get Color of user
  function getColor(username) {
    return $.ajax({
      type: "POST",
      url: "ajax/get_color.php",
      data: { username: username },
      async: false
    }).responseText;
  }
  //Main Screens Drawing
  //Setting Username and room_Type screen
  function AddSettingScreen() {
    $(
      '<div class="row setting_row"> <div class="col-12"> <div class="card"> <div class="card-body"> <input type="text" class="form-control username" placeholder="Set Username"><hr><div class="col-12"><div class="row"><div class="col-6"><input type="radio" name="type" class="chatTypeRadio" value="Group" id="GroupChat"><label class="chatTypeLabel" for="GroupChat">Group Chat</label></div><div class="col-6"><input type="radio" name="type" class="chatTypeRadio" value="Duo" id="DuoChat"><label class="chatTypeLabel" for="DuoChat">Duo Chat</label></div></div></div> <button class="btn float-right btn-md enter_chat" disabled><i class="fas fa-arrow-right"></i> Enter Chat</button> </div></div></div></div>'
    ).appendTo(".container");
  }
  //Chat Screent
  function AddChatScreen() {
    $(
      '<div class="row msgs_row"> <div class="col-12"> <div class="card"> <div class="card-body"></div></div></div></div><hr> <div class="row sending_box"> <div class="col-12"> <textarea class="form-control msg" placeholder="Message"></textarea> <button class="btn btn-danger disconnect"><i class="fas fa-sign-out-alt fa-rotate-180"></i> Disconnect</button><button class="btn btn-lg float-right send_message" disabled><i class="fas fa-paper-plane"></i> Send</button> </div></div>'
    ).appendTo(".container");
  }
  //Empty the Container
  function EmptyContainer(callback, param = "") {
    $(".container").fadeOut("slow", function() {
      $(this).empty();
      callback(param);
      $(this).fadeIn("slow");
    });
  }
  //Remove Sending Box => When entering Duo room and other client disconnected
  function RemoveSendingBox() {
    //Unbind Events
    $(document).off("click", ".send_message");
    $(document).off("click", ".disconnect");
    //Remove
    $(".sending_box").fadeOut("slow", function() {
      $("hr").fadeOut("slow", function() {
        $(this).remove();
      });
      $(this).remove();
    });
  }
  //Remove All User Messages => When entering Group room and other client disconnected
  function RemoverUserMessages(username) {
    $("*")
      .find('h5[data-sender="' + username + '"]')
      .hide("slow", function() {
        $(this).remove();
      });
  }
  //Handle Buttons classes[Styles] and disable attribute
  function HandleBtns(type) {
    //Entering Chat Btn
    if (type == "1") {
      //If user is written , Room type is selected
      if (
        $(".username")
          .val()
          .trim() != "" &&
        $('input[name="type"]:checked').length > 0
      ) {
        $(".enter_chat").removeAttr("disabled");
        $(".enter_chat").addClass("hover");
        //If user isnt't written , Room type isn't selected
      } else {
        $(".enter_chat").removeClass("hover");
        $(".enter_chat").attr("disabled", "disabled");
      }
      //Send Msg btn
    } else {
      var msg = $(".msg")
        .val()
        .trim();
      //If Msg is written
      if (msg != "") {
        $(".send_message").removeAttr("disabled");
        $(".send_message").addClass("hover");
        ////If no Msg is written
      } else {
        $(".send_message").removeClass("hover");
        $(".send_message").attr("disabled", "disabled");
      }
    }
  }
  //Handle Chat Type Radio Button
  function HandleRadioButtons(selector) {
    $(".chatTypeRadio").removeAttr("checked");
    $(selector).attr("checked", "checked");
  }
  //Connection
  //Drawings
  function PendingConnectionScreen() {
    $(
      '<div class="row"> <div class="col-12 col-md-6"> <div class="alert alert-warning"> <h4><i>Waiting for connection...</i></h4> </div></div></div>'
    ).appendTo(".container");
  }
  function ConnectingScreen(username) {
    $(
      ' <div class="row"> <div class="col-12 col-md-6"> <div class="alert alert-success"> <h4><i>Connecting with ' +
        username +
        "</i></h4> </div></div></div>"
    ).appendTo(".container");
  }
  //Wrapping Messages
  //Format
  function formatTimeStamp(time) {
    var adjust = new Date(time * 1000 + 7200000), // *100 to convert into js readable timestamp(miliseconds) , +7200000 cuz of UTC+2
      iso = adjust.toISOString(),
      formatted =
        iso.split("T")[1].split(".")[0] +
        " " +
        iso.split("T")[0].split("-")[2] +
        "/" +
        iso.split("T")[0].split("-")[1] +
        "/" +
        iso.split("T")[0].split("-")[0];
    return formatted;
  }
  function wrap_normal_message(
    client_username,
    sender_username,
    message,
    color,
    time
  ) {
    var formatted_time = formatTimeStamp(time);
    var sender_part;
    if (client_username == sender_username) {
      sender_part = "You: ";
    } else {
      sender_part = sender_username + ": ";
    }
    return (
      "<h5 style='color:" +
      color +
      "' data-sender='" +
      sender_username +
      "'>" +
      sender_part +
      message +
      " <small class='float-right'>" +
      formatted_time +
      "</small></h5>"
    );
  }
  function wrap_connected_message(
    client_username,
    connector_username,
    color,
    time
  ) {
    var formatted_time = formatTimeStamp(time);
    var connector_part, verb;
    if (client_username == connector_username) {
      connector_part = "You ";
      verb = "have ";
    } else {
      connector_part = connector_username + " ";
      verb = "has ";
    }
    return (
      "<h5 style='color:" +
      color +
      "' data-sender='" +
      connector_username +
      "'>" +
      connector_part +
      verb +
      " joined <small class='float-right'>" +
      formatted_time +
      "</small></h5>"
    );
  }
  function wrap_disconnected_message(username, type) {
    if (type == "Duo") {
      return (
        "<h5 style='color:red;'>Unfortunately, " +
        username +
        " has disconnected, You will be disconnected in 3 seconds.</h5>"
      );
    } else {
      return "<h5 style='color:red;'>" + username + " has disconnected.</h5>";
    }
  }
  //Fetch Messages of username room
  function fetchMessages(current_username) {
    var MsgList = $(".msgs_row .card-body");
    $.ajax({
      type: "POST",
      url: "ajax/get_messages.php",
      data: { username: current_username },
      success: function(response) {
        var data = JSON.parse(response);
        //If more than Message
        if (data.message_type == undefined) {
          for (message in data) {
            var msg_type, msg_time, msg_username, msg_color, msg_text;
            (msg_type = data[message].message_type),
              (msg_time = data[message].time);
            msg_text = data[message].text;
            msg_username = data[message].sender_username;
            msg_color = data[message].color;
            if (msg_type == "3") {
              MsgList.append(
                wrap_connected_message(
                  current_username,
                  msg_username,
                  msg_color,
                  msg_time
                )
              );
            } else if (msg_type == "4") {
              MsgList.append(
                wrap_normal_message(
                  current_username,
                  msg_username,
                  msg_text,
                  msg_color,
                  msg_time
                )
              );
            }
          }
          //If Only a message
        } else {
          var msg_type, msg_time, msg_username, msg_color, msg_text;
          (msg_type = data.message_type), (msg_time = data.time);
          msg_text = data.text;
          msg_username = data.sender_username;
          msg_color = data.color;
          if (msg_type == "3") {
            MsgList.append(
              wrap_connected_message(
                current_username,
                msg_username,
                msg_color,
                msg_time
              )
            );
          } else if (msg_type == "4") {
            MsgList.append(
              wrap_normal_message(
                current_username,
                msg_username,
                msg_text,
                msg_color,
                msg_time
              )
            );
          }
        }
      }
    });
  }
  //Events
  $(document).on("click", ".enter_chat", function() {
    var type = $('input[name="type"]:checked').val(),
      username = $(".username")
        .val()
        .trim();
    if (checkUsername(username) == "valid") {
      //Check if cookie isn't set [Prevent Opening Two Tabs joinig by one and rejoin by other]
      if (!CheckCookie("username")) {
        //Set Cookies
        $.cookie("username", username);
        $.cookie("type", type);
        //Start Connection
        StartConnection(type, username);
      }
      //If Username is invalid
    } else {
      $(".card-body .alert-danger").hide("slow", function() {
        $(this).remove();
      });
      $(
        '<div class="row"><div class="col-12"><div class="alert alert-danger"><b>' +
          checkUsername(username) +
          "</b></div></div></div>"
      )
        .hide()
        .appendTo(".card-body")
        .show("slow");
    }
  });
  //Handle Chat Type Button
  $(document).on("change", ".chatTypeRadio", function() {
    HandleRadioButtons(this);
    HandleBtns("1");
  });
  //Handle Send Button
  $(document).on("input", ".username", function() {
    HandleBtns("1");
  });
  //Main Section
  //On Joining
  //If cookie is set
  if (CheckCookie("username")) {
    $(".container").fadeOut(function() {
      $(this).empty();
      $(this).fadeIn();
      //Start connection
      AddChatScreen();
      StartConnection("exist", $.cookie("username"));
    });
  } else {
    EmptyContainer(AddSettingScreen);
  }
  //Start Connection
  function StartConnection(type, username) {
    //Get Time
    var time = Math.floor(new Date().getTime() / 1000);
    //Adjust Type
    var socket = new WebSocket(
      "ws://localhost:8080/?type=" +
        type +
        "&username=" +
        username +
        "&time=" +
        time
    );
    socket.onopen = function(e) {
      if (type == "exist") {
        fetchMessages(username);
      }
    };
    socket.onmessage = function(e) {
      var Message_Object = JSON.parse(e.data);
      if (Message_Object.type == "1") {
        EmptyContainer(PendingConnectionScreen);
        //IF Client left during waiting
        window.addEventListener("beforeunload", event => {
          // Cancel the event as stated by the standard.
          event.preventDefault();
          //Prepare Message
          var to_send = {
            type: "6",
            username: username
          };
          //Send Message
          socket.send(JSON.stringify(to_send));
          //Remove Cookies
          $.removeCookie("username");
          $.removeCookie("type");
        });
      } else if (Message_Object.type == "2") {
        //Remove Listener
        window.removeEventListener("beforeunload", event => {
          // Cancel the event as stated by the standard.
          event.preventDefault();
          //Prepare Message
          var to_send = {
            type: "6",
            username: username
          };
          //Send Message
          socket.send(JSON.stringify(to_send));
          $.removeCookie("username");
          $.removeCookie("type");
        });
        //Show Connecting Message
        EmptyContainer(ConnectingScreen, Message_Object.username);
        //Leave for 2 Seconds
        setTimeout(function() {
          //Add Chat Screen
          EmptyContainer(AddChatScreen);
          setTimeout(function() {
            //Let Dom Load
            var Message_Box = $(".card-body");
            //Get Color
            var color = getColor(username);
            //Wrap Joined Message
            Message_Box.append(
              wrap_connected_message(
                username,
                username,
                color,
                Message_Object.time
              )
            );
            Message_Box.append(
              wrap_connected_message(
                username,
                Message_Object.username,
                Message_Object.color,
                Message_Object.time
              )
            );
          }, 1000);
        }, 2000);
      } else if (Message_Object.type == "3") {
        var Message_Box = $(".card-body");
        Message_Box.append(
          wrap_connected_message(
            username,
            Message_Object.username,
            Message_Object.color,
            Message_Object.time
          )
        );
      } else if (Message_Object.type == "4") {
        var Message_Box = $(".card-body");
        Message_Box.append(
          wrap_normal_message(
            username,
            Message_Object.username,
            Message_Object.text,
            Message_Object.color,
            Message_Object.time
          )
        );
      } else if (Message_Object.type == "5") {
        var Message_Box = $(".card-body");
        Message_Box.append(
          wrap_disconnected_message(Message_Object.username, $.cookie("type"))
        );
        if ($.cookie("type") == "Duo") {
          //Remove Sending Box
          RemoveSendingBox();
          //Leave 3 seconds then close
          setTimeout(function() {
            socket.close();
          }, 3000);
        } else {
          RemoverUserMessages(Message_Object.username);
        }
      } else if (Message_Object.type == "7") {
        EmptyContainer(AddChatScreen);
        setTimeout(function() {
          //Load Dom
          fetchMessages(username);
        }, 1000);
      }
    };
    socket.onclose = function() {
      EmptyContainer(AddSettingScreen);
      $.removeCookie("username");
      $.removeCookie("type");
      //Unbind Events
      $(document).off("click", ".send_message");
      $(document).off("click", ".disconnect");
    };
    //Sending Message
    //Handling Send Button
    $(document).on("input", ".msg", function() {
      HandleBtns("2");
    });
    //Send Message
    $(document).on("click", ".send_message", function() {
      var msg = $(".msg")
          .val()
          .trim(),
        time = Math.floor(new Date().getTime() / 1000),
        color = getColor(username),
        Message_Box = $(".card-body");
      var Message = {
        type: "4",
        username: username,
        color: color,
        time: time,
        text: msg
      };
      socket.send(JSON.stringify(Message));
      Message_Box.append(
        wrap_normal_message(username, username, msg, color, time)
      );
      //Empty Message Input
      $(".msg").val("");
      //Handle Send Button => Make disabled
      HandleBtns("2");
    });
    //Disconnect
    $(document).on("click", ".disconnect", function() {
      //Unset Cookies
      $.removeCookie("username");
      $.removeCookie("type");
      //Unbind Events
      $(document).off("click", ".send_message");
      $(document).off("click", ".disconnect");
      //Send Message
      var Message = {
        type: "5",
        username: username
      };
      socket.send(JSON.stringify(Message));
      socket.close();
    });
  }
});
