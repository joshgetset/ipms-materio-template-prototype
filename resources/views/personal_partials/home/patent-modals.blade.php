<!-- Patent Search Modal -->

<div
    id="patentSearchModal"
    class="patent-modal hidden"
>

    <div
        class="patent-modal-overlay"
        id="patentModalOverlay"
    ></div>


    <div class="patent-modal-dialog">

        <button
            type="button"
            class="patent-modal-close"
            id="patentModalClose"
            aria-label="Close"
        >
            &times;
        </button>


        <div class="modal-header">

            <div class="modal-label">
                PATENT SEARCH
            </div>

            <h2 id="patentModalTitle">
                Search Results
            </h2>

            <p id="patentModalDescription">
                Results from the Lens patent database.
            </p>

        </div>


        <div
            id="patentResults"
            class="patent-results"
        ></div>

    </div>

</div>


<!-- Patent Details Modal -->

<div
    id="patentDetailsModal"
    class="patent-modal hidden"
>

    <div
        class="patent-modal-overlay"
        id="patentDetailsOverlay"
    ></div>


    <div class="patent-modal-dialog details-dialog">

        <button
            type="button"
            class="patent-modal-close"
            id="patentDetailsClose"
            aria-label="Close"
        >
            &times;
        </button>


        <div id="patentDetailsContent"></div>

    </div>

</div>