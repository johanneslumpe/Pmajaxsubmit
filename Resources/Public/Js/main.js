(function ($, document, window, undefined) {
  $(document).ready(function () {

    $('form.powermail_form').submit(function (e) {
      e.preventDefault();
      var form = $(this);
      var url  = form.attr('action');

      $.ajax({
        type: 'POST',
        url: url,
        data: form.serialize()
      })
      .done(function (data) {

        form.prepend(data.message);
        // reset the background color, if we failed earlier
        $('.powermail_field').css('backgroundColor', 'inherit');

      })
      // This case should only ever happen, if somebody manipulates the
      // form and manually removes validations.
      // If this is the case then we simply color the invalid fields red,
      // without paying too much attention to invoking the proper validations
      // (since the user has probably removed on or more validation classes)
      .fail(function (xhr) {

        var response = JSON.parse(xhr.responseText);
        var errors = response.errors;
        for (var error in errors) {
          if (errors.hasOwnProperty(error)) {
            $('#' + error).css('backgroundColor', '#f00');
          }
        }

      });
    });

  });
}(jQuery, document, window));