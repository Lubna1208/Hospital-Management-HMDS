// Example: toggle password in patient portal forms
function togglePassword(btnId, inputId){
    const btn = document.getElementById(btnId);
    const input = document.getElementById(inputId);
    
    if(input.type === "password"){
        input.type = "text";
        btn.textContent = "Hide";
    } else {
        input.type = "password";
        btn.textContent = "Show";
    }
}

// Add more JS functions here for appointments, reports, etc.
