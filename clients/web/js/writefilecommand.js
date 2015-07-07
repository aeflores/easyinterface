window.WriteFileCommand = (function() {
    "use strict";

    WriteFileCommand.parse = function(c, ei_info) { 
	var tag = c[0].tagName;
	if ( tag != _ei.outlang.syntax.writefile ) return null;

	var filename = c.attr(_ei.outlang.syntax.filename);
	if ( !filename )
	    throw "The 'filename' is missing in the "+_ei.outlang.syntax.writefile+ " command";

	var overwrite = c.attr(_ei.outlang.syntax.overwrite);
	if ( overwrite == "true" )
	    overwrite = true;
	else
	    overwrite = false;

	var content  = $(c).text();

	return new WriteFileCommand({
	    filename: filename,
	    content: content,
	    overwrite: overwrite,
	    filemanager: ei_info.filemanager
	});
    };

    function WriteFileCommand(options) {	
	this.filename = options.filename;
	this.content = options.content;
	this.overwrite = options.overwrite;
	this.filemanager = options.filemanager;
    };

    WriteFileCommand.prototype = {
	constructor: WriteFileCommand,

	//
	"do":
	function() {
	    this.filemanager.createFile(this.filename, this.content, this.overwrite);	    
	},

	//
	"undo":
	function() {
	}
    }

    return WriteFileCommand;

})();
