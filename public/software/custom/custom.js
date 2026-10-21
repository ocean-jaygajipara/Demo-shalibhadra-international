function isNumber(evt) {
    evt = (evt) ? evt : window.event;
    var charCode = (evt.which) ? evt.which : evt.keyCode;
    if (charCode > 31 && (charCode < 48 || charCode > 57)) {
        return false;
    }
    return true;
}

function isAlphanumeric(evt) {
    evt = evt || window.event;
    var charCode = evt.which || evt.keyCode;
    var charStr = String.fromCharCode(charCode);

    if (!charStr.match(/^[a-zA-Z0-9 ]$/)) {
        evt.preventDefault();
        return false;
    }
    return true;
}

function isDecimal(evt) {
    evt = evt || window.event;
    var charCode = evt.which || evt.keyCode;

    if ([8, 46, 37, 39].includes(charCode)) {
        return true;
    }

    if (charCode === 46) {
        if (evt.target.value.includes('.')) {
            return false;
        }
        return true;
    }

    if (charCode >= 48 && charCode <= 57) {
        return true;
    }
    return false;
}

function formatDateTime(date) {
    const pad = (n) => (n < 10 ? '0' + n : n);

    const year = date.getFullYear();
    const month = pad(date.getMonth() + 1); // Months are zero-based
    const day = pad(date.getDate());

    const hours = pad(date.getHours());
    const minutes = pad(date.getMinutes());
    const seconds = pad(date.getSeconds());

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function formatToDMYAMPM(input) {
    const dateObj = input instanceof Date ? input : new Date(input.replace(/-/g, "/"));

    const day = String(dateObj.getDate()).padStart(2, '0');
    const month = String(dateObj.getMonth() + 1).padStart(2, '0');
    const year = dateObj.getFullYear();

    let hours = dateObj.getHours();
    const minutes = String(dateObj.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';

    hours = hours % 12;
    hours = hours ? hours : 12;

    return `${day}-${month}-${year} ${String(hours).padStart(2, '0')}:${minutes} ${ampm}`;
}

const ckeditorInstances = {}; // Global object to hold instances

function initializeCKEditor(selector, basicItems = [], additionalConfig = {}, editorHeight = '180px') {
    const defaultToolbar = {
        items: basicItems.length > 0 ? basicItems : [
            'undo', 'redo', '|',
            'heading', '|',
            'bold', 'italic', '|',
            'link', 'insertImage', 'insertTable', 'blockQuote', 'mediaEmbed', '|',
            'bulletedList', 'numberedList', '|',
            'alignment', '|',
            'sourceEditing'
        ],
        shouldNotGroupWhenFull: true
    };

    const defaultConfig = {
        toolbar: defaultToolbar,
        placeholder: 'Start typing...',
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' }
            ]
        },
        removePlugins: [
            'CKBox', 'CKFinder', 'EasyImage', 'RealTimeCollaborativeComments',
            'RealTimeCollaborativeTrackChanges', 'RealTimeCollaborativeRevisionHistory',
            'PresenceList', 'Comments', 'TrackChanges', 'TrackChangesData',
            'RevisionHistory', 'Pagination', 'WProofreader', 'MathType'
        ]
    };

    const finalConfig = Object.assign({}, defaultConfig, additionalConfig);

    document.querySelectorAll(selector).forEach((element, index) => {
        CKEDITOR.ClassicEditor.create(element, finalConfig)
            .then(editor => {
                editor.editing.view.change(writer => {
                    writer.setStyle("min-height", editorHeight, editor.editing.view.document.getRoot());
                });

                // Save instance by element ID
                const elementId = element.id || `ckeditor-${index}`;
                ckeditorInstances[elementId] = editor;

                // console.log(`Editor ${elementId} initialized`, editor);
            })
            .catch(error => {
                console.error(`Error initializing editor:`, error);
            });
    });
}

if($('meta[name="currentGuard"]').attr('value') == 'team_person' && ($('meta[name="parent_type_id"]').attr('value') != '')){

    function detectBrowserFromUA() {
        const userAgent = navigator.userAgent;

        if (userAgent.includes("Chrome") && !userAgent.includes("Edg") && !userAgent.includes("OPR")) {
            return "chrome";
        } else if (userAgent.includes("Safari") && !userAgent.includes("Chrome")) {
            return "safari";
        } else if (userAgent.includes("Firefox")) {
            return "firefox";
        } else if (userAgent.includes("MSIE") || userAgent.includes("Trident")) {
            return "ie";
        } else if (userAgent.includes("Edg")) {
            return "msedge";
        } else if (userAgent.includes("OPR") || userAgent.includes("Opera")) {
            return "opera";
        } else {
            return "unknown";
        }
    }

    function getBrowserDetails() {
        var navigatorDetails = {};
        for (var key in navigator) {
            try {
                var value = navigator[key];
                if (typeof value !== "function" && typeof value !== "object") {
                    navigatorDetails[key] = value;
                }
            } catch (e) {
                navigatorDetails[key] = "Access denied or unavailable";
            }
        }

        navigatorDetails.detected_browser = detectBrowserFromUA();
        return navigatorDetails;
    }

    // Function to get geolocation
    // function getGeolocation(callback) {
    //     if (navigator.geolocation) {
    //         navigator.geolocation.getCurrentPosition(
    //             function (position) {
    //                 const locationDetails = {
    //                     latitude: position.coords.latitude,
    //                     longitude: position.coords.longitude,
    //                     accuracy: position.coords.accuracy,
    //                     timestamp: new Date(position.timestamp).toLocaleString()
    //                 };
    //                 callback(null, locationDetails); // Success
    //             },
    //             function (error) {
    //                 callback(error.message, null); // Error
    //             }
    //         );
    //     } else {
    //         callback("Geolocation is not supported by this browser.", null);
    //     }
    // }

    function getGeolocation(callback) {
        if (navigator.geolocation) {
            navigator.permissions.query({ name: 'geolocation' }).then(function (result) {
                if (result.state === 'granted' || result.state === 'prompt') {
                    navigator.geolocation.getCurrentPosition(
                        function (position) {
                            const locationDetails = {
                                latitude: position.coords.latitude,
                                longitude: position.coords.longitude,
                                accuracy: position.coords.accuracy,
                                timestamp: new Date(position.timestamp).toLocaleString()
                            };
                            callback(null, locationDetails); // Success
                        },
                        function (error) {
                            callback(error.message, null); // Error
                        }
                    );
                } else {
                    // Permission denied earlier
                    callback("Permission denied", null);
                }
            });
        } else {
            callback("Geolocation is not supported by this browser.", null);
        }
    }


    // Example usage
    function getGeolocationcall(){
        const browserDetails = getBrowserDetails();
        $('#navigator_browser_info').attr("data-navigator-browser-info",JSON.stringify(browserDetails, null, 2));

        getGeolocation(function (error, locationDetails) {
            if (error) {
                $(".buy-now").hide();
                //$('#navigator_location_info').attr("data-navigator-location-info",error);
                // if (typeof punchinout_url !== 'undefined' && punchinout_url) {
                //     window.location.href = punchinout_url;
                // }
            } else {
                $('#navigator_location_info').attr("data-navigator-location-info",JSON.stringify(locationDetails, null, 2));
                 $(".buy-now").show();
                // if (window.location.pathname === "/software/permissions") {
                //     var locationData = $('#navigator_location_info').attr("data-navigator-location-info");
                //     var parsedLocation = locationData ? JSON.parse(locationData) : {};

                //     if (parsedLocation.latitude && parsedLocation.longitude) {
                //         window.location.href = dashboard_url;
                //     }
                // }
                // $('body').css({
                //     'opacity': '',
                //     'pointer-events': ''
                // });
            }
        });
    }
    getGeolocationcall();
    // let details = getBrowserDetails();
}

function formatWeight(val) {
    val = val.toString().trim();
    if (val === "" || val === "." || val.endsWith(".")) return val;

    let num = parseFloat(val);
    if (isNaN(num)) return "";

    let intPart = Math.floor(num);
    let decimal = Math.round((num % 1) * 1000);
    let decStr = decimal.toString().padStart(3, "0");

    let firstDigit = parseInt(decStr[0]);
    let lastTwo = parseInt(decStr.slice(1));
    let newFirstDigit = firstDigit;

    let finalDecimal = 0;

    if (lastTwo <= 29) {
        finalDecimal = newFirstDigit * 100;
    }
    else if (lastTwo <= 79) {
        finalDecimal = newFirstDigit * 100 + 50;
    }
    else {
        newFirstDigit = firstDigit + 1;
        finalDecimal = newFirstDigit * 100;
    }
    return (intPart + finalDecimal / 1000).toFixed(3);
}

