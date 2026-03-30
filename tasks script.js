let points = 0
let hours = 0
let completedTasks = 0

function addTask(){

let name = document.getElementById("taskName").value
let studyHours = parseFloat(document.getElementById("taskHours").value)

if(name === "" || isNaN(studyHours)) return

let li = document.createElement("li")

li.innerHTML =
name + " (" + studyHours + "h) " +
"<button onclick='completeTask(this,"+studyHours+")'>Complete</button>"

document.getElementById("taskList").appendChild(li)

document.getElementById("taskName").value = ""
document.getElementById("taskHours").value = ""

}

function completeTask(button,h){

points += h * 10
hours += h
completedTasks++

updateDashboard()

button.parentElement.remove()

}

function updateDashboard(){

if(document.getElementById("points"))
document.getElementById("points").innerText = points

if(document.getElementById("hours"))
document.getElementById("hours").innerText = hours

if(document.getElementById("tasksCompleted"))
document.getElementById("tasksCompleted").innerText = completedTasks

if(document.getElementById("summary"))
document.getElementById("summary").innerText =
"You studied " + hours + " hours and earned " + points + " points."

}
