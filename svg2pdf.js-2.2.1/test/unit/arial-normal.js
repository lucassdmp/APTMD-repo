﻿;(function(jsPDFAPI) {
  const font =
  const callAddFont = function() {
    this.addFileToVFS('arial-normal.ttf', font)
    this.addFont('arial-normal.ttf', 'Arial', 'normal')
  }
  jsPDFAPI.events.push(['addFonts', callAddFont])
})(jsPDF.API)