// Dummy JS file for testing purposes
console.log("This is a dummy JavaScript file for testing purposes.");

// Simple variables
const name = "Test User";
let count = 0;

// Basic function
function incrementCount() {
	count += 1;
	console.log(`Count is now: ${count}`);
	return count;
}

// Object example
const config = {
	enabled: true,
	timeout: 3000
};

// Event handling
document.addEventListener('click', function () {
	incrementCount();
});

// Export if needed
export {incrementCount, config};