* Duo Chat Rooms
* Group Chat Rooms [Max 10 Clients a room]
### Made By
* Ratchet
* PHp [OOP]
* Jquery / Jquery Cookies
* Ajax [Jquery]
## Important Notes
* Script isn't 100% ready to use on real applications as : 
   * Using Cookies isn't recommended.
   * Making Client connection with Username isn't recommended.
### How it Works
#### User Choose a username which saved as a cookie to be able to reconnect.
* Duo Rooms
   * If Client join and other Client found on waiting room is created , Both enter it.
   * Otherwise Client will be inserted to waiting.
* Group Rooms
   * Client Join any room which has less than 10 clients.
   * If No Room with less than 10 client exist, A new Room is created.
### Messages Types
* 1 -> Pending Message
   * Sent to Client to wait for other client to join.
   * Duo Rooms Only.
* 2 -> Connecting Message
   * Sent to Clients to inform them who they are connecting to.
   * Duo Rooms Only.
* 3 -> Connected Message
   * Sent to Clients to inform them client joined.
   * Duo & Group Rooms.
* 4 -> Normal Message
   * Sent to other room clients.
   * Duo & Group Rooms.
* 5 -> Disconnected Message
   * Sent to other clients that current client disconnected.
   * Duo & Group Rooms.
* 6 -> Client Disconnect During Waiting
   * Delete From Waiting.
* 7 -> Joined
   * Group Rooms Only.
