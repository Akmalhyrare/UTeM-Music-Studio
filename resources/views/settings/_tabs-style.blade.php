<style>
    .settings-tab {
        font-size: 13px;
        border-radius: 8px;
        border: 1px solid #E5E7EB;
        background: #fff;
        color: #374151;
        padding: 8px 18px;
    }

    .settings-tab.active {
        background: #7C3AED;
        color: #fff;
        border-color: #7C3AED;
    }

    @media (max-width: 576px) {
        #settings-tabs {
            flex-wrap: wrap;
        }

        #settings-tabs .settings-tab {
            flex: 1 1 calc(50% - 8px);
            text-align: center;
        }
    }
</style>
