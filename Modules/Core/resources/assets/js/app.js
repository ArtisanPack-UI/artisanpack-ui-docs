// Add copy buttons to code blocks
function addCopyButtons() {
    const codeContainers = document.querySelectorAll('.code-block-container');
    console.log(`Found ${codeContainers.length} code block containers`);

    codeContainers.forEach((container, index) => {
        // Skip if button already exists
        if (container.querySelector('.copy-code-button')) {
            console.log(`Container ${index} already has a copy button`);
            return;
        }

        const codeBlock = container.querySelector('code');
        if (!codeBlock) {
            console.log(`Container ${index} has no code element`);
            return;
        }

        console.log(`Adding copy button to container ${index}`);

        // Create copy button
        const button = document.createElement('button');
        button.className = 'copy-code-button';
        button.type = 'button'; // Prevent form submission
        button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
        `;
        button.setAttribute('aria-label', 'Copy code to clipboard');
        button.setAttribute('title', 'Copy code');

        // Add click event
        button.addEventListener('click', async (e) => {
            e.preventDefault();
            e.stopPropagation();

            console.log('Copy button clicked');
            const code = codeBlock.textContent;
            let copySuccessful = false;

            // Try modern clipboard API first
            if (navigator.clipboard && navigator.clipboard.writeText) {
                try {
                    await navigator.clipboard.writeText(code);
                    console.log('Code copied successfully using Clipboard API');
                    copySuccessful = true;
                } catch (err) {
                    console.error('Clipboard API failed:', err);
                }
            }

            // Fallback for HTTP or older browsers
            if (!copySuccessful) {
                try {
                    const textarea = document.createElement('textarea');
                    textarea.value = code;
                    textarea.style.position = 'fixed';
                    textarea.style.left = '-9999px';
                    textarea.style.top = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();

                    const successful = document.execCommand('copy');
                    document.body.removeChild(textarea);

                    if (successful) {
                        console.log('Code copied using fallback method');
                        copySuccessful = true;
                    } else {
                        console.error('Fallback copy command returned false');
                    }
                } catch (fallbackErr) {
                    console.error('Fallback copy failed:', fallbackErr);
                }
            }

            // Show visual feedback if copy was successful
            if (copySuccessful) {
                button.classList.add('copied');
                button.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                `;
                button.setAttribute('title', 'Copied!');

                // Reset after 2 seconds
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    `;
                    button.setAttribute('title', 'Copy code');
                }, 2000);
            } else {
                console.error('All copy methods failed');
            }
        });

        container.appendChild(button);
        console.log(`Copy button added to container ${index}`);
    });
}

// Initialize Prism and copy buttons
function initializeCodeBlocks() {
    console.log('Initializing code blocks...');

    // Wait for Prism to highlight
    if (typeof Prism !== 'undefined') {
        console.log('Prism found, highlighting code...');
        Prism.highlightAll();
    } else {
        console.log('Prism not found');
    }

    // Add copy buttons after a short delay to ensure Prism has finished
    setTimeout(() => {
        console.log('Adding copy buttons...');
        addCopyButtons();
    }, 100);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', initializeCodeBlocks);

// Re-initialize after Livewire navigation
document.addEventListener('livewire:navigated', () => {
    console.log('Livewire navigated, re-initializing code blocks...');
    initializeCodeBlocks();
});