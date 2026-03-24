import { Server } from "socket.io";
import http from "http";

const server = http.createServer();
const io = new Server(server, {
  cors: { origin: "*" }, // allow all origins for dev
});

io.on("connection", (socket) => {
  console.log("Client connected:", socket.id);

  // Listen for events from any client
  socket.on("notification-event", (data) => {
    console.log("Received:", data);

    // Broadcast to all other clients except sender
    socket.broadcast.emit("notification-event", data);
  });
});

server.listen(3001, "0.0.0.0", () => {
  console.log("✅ Socket.IO server running on port 3001");
});
