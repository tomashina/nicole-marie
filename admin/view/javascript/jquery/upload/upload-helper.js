function formatFilesize(size) {
  var i = 0,
    suffix = new Array(
      'B',
      'KB',
      'MB',
      'GB',
      'TB'
    );
  while ((size / 1024) >= 1) {
    size /= 1024;
    i++;
  }
  size = Math.round(size * 100) / 100;

  return size + suffix[i];
}

function truncateFilename(filename, length) {
  var ext = filename.split('.').pop();
  if (filename.length > length) {
    return (filename.substr(0, length) + '...' + ext);
  }
  return filename;
}