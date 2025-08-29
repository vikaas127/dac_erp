Dropzone.options.flexImagesUpload = false;
$(function () {
  init_editor(".tinymce-event-description");

  $("#tags").tagit({
    placeholderText: "Enter comma-separated tags",
    allowSpaces: true,
  });

  if ($("#flex-images-upload").length > 0) {
    new Dropzone(
      "#flex-images-upload",
      appCreateDropzoneOptions({
        paramName: "file",
        uploadMultiple: true,
        parallelUploads: 7,
        maxFiles: 7,
        accept: function (file, done) {
          done();
        },
        success: function (file, response) {
          window.location.reload();
        },
      })
    );
  }
});

function flexstage_toggle_view_type(active, general) {
  if (active === ".container-hybrid") {
    $(general).removeClass("hidden");
    return true;
  }
  if ($(general).length) $(general).addClass("hidden");
  if ($(active).length) $(active).removeClass("hidden");
}
