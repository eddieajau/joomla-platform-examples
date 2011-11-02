/**
 * 
 */

var SocketConnector = new Class({
	logger: null,
	socket: null,

	/**
	 * @param   object  The textarea to send log messages to.
	 */
	initialize: function(logger) {
		this.logger = logger;

		if ("WebSocket" in window) {
			this.log('Checking ... sockets available.');
			
			uri = "ws://127.0.0.1:8000/examples/socket/server.php";
			
			try {
				this.socket = new WebSocket(uri);
				
				this.log(this.socket.readyState);
				
				this.socket.onopen = function() {
					this.log('Socket opening: '+this.socket.readyState);
				}
				
				this.socket.onmessage = function(message) {
					this.log('Received: '+message.data);
				}
				
				this.socket.onclose = function() {
					this.log('Socket closing: '+this.socket.readyState);
				}
			} catch (exception) {
				this.log('Error: '.exception);
			}
		} else {
			this.log('No sockets available.');
		}
	},
	
	/**
	 * Append a log message to the log textarea.
	 * 
	 * @param  string  The message.
	 */
	log: function(message) {
		if (this.logger) {
			html = this.logger.get('value').trim();
			if (html) {
				html += "\n";
			}
			this.logger.set('value', html + message);
		}
	},
	
	send: function(message) {
		try {
			this.socket.send(message);
			this.log('Sending: '+message);
		} catch (exception) {
			this.log('Error: '.exception);
		}
	}
});
