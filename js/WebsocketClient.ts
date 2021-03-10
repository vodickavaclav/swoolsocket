const WebSocket = require('isomorphic-ws');

export default class WebsocketClient {
    public init (): void {
        WebsocketClient.connect();
    }

    private static connect () {
        const ws = new WebSocket('ws://localhost:9502/');
        try {
            ws.onopen = function open () {
                WebsocketClient.log('✅ Websocket connected');
            };
        } catch (err) {
            WebsocketClient.log(err);
        }

        ws.onclose = function close () {
            WebsocketClient.log('❌ Websocket disconnected');
            setTimeout(function () {
                WebsocketClient.connect();
            }, 1000);
        };

        ws.onmessage = function incoming (e) {
            WebsocketClient.log(`Roundtrip time: ${Date.now()} ms`);
        };

        ws.onerror = function error (err) {
            console.error('Socket encountered error: ', err.message, 'Closing socket');
            ws.close();
        };
    }

    private static log (message): void {
        if (process.env.NODE_ENV !== 'production') {
            console.log(message);
        }
    }
}
