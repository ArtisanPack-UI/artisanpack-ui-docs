// Add copy buttons to code blocks
function addCopyButtons() {
    const codeBlocks = document.querySelectorAll('pre code');

    codeBlocks.forEach((codeBlock) => {
        // Skip if button already exists
        if (codeBlock.parentElement.querySelector('.copy-code-button')) {
            return;
        }

        // Create wrapper if needed
        const pre = codeBlock.parentElement;
        if (!pre.classList.contains('code-block-wrapper')) {
            pre.classList.add('code-block-wrapper');
        }

        // Create copy button
        const button = document.createElement('button');
        button.className = 'copy-code-button';
        button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
            </svg>
        `;
        button.setAttribute('aria-label', 'Copy code to clipboard');
        button.setAttribute('title', 'Copy code');

        // Add click event
        button.addEventListener('click', async () => {
            const code = codeBlock.textContent;

            try {
                await navigator.clipboard.writeText(code);

                // Show success state
                button.classList.add('copied');
                button.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                `;
                button.setAttribute('title', 'Copied!');

                // Reset after 2 seconds
                setTimeout(() => {
                    button.classList.remove('copied');
                    button.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    `;
                    button.setAttribute('title', 'Copy code');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy code:', err);
            }
        });

        pre.appendChild(button);
    });
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', addCopyButtons);

// Ensure Alpine/Livewire navigation events are handled
document.addEventListener('livewire:navigated', () => {
    // Re-initialize code copy buttons after navigation
    addCopyButtons();
});