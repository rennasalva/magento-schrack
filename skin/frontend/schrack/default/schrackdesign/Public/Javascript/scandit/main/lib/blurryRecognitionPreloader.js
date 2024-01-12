"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
exports.BlurryRecognitionPreloader = void 0;
var tslib_1 = require("tslib");
var eventemitter3_1 = require("eventemitter3");
var __1 = require("..");
var barcode_1 = require("./barcode");
var browserHelper_1 = require("./browserHelper");
var dataCaptureLoader_1 = require("./dataCaptureLoader");
var dataCaptureWorker_1 = require("./workers/dataCaptureWorker");
var BlurryRecognitionPreloaderEventEmitter = /** @class */ (function (_super) {
    tslib_1.__extends(BlurryRecognitionPreloaderEventEmitter, _super);
    function BlurryRecognitionPreloaderEventEmitter() {
        return _super !== null && _super.apply(this, arguments) || this;
    }
    return BlurryRecognitionPreloaderEventEmitter;
}(eventemitter3_1.EventEmitter));
var BlurryRecognitionPreloader = /** @class */ (function () {
    function BlurryRecognitionPreloader(preload) {
        this.eventEmitter = new eventemitter3_1.EventEmitter();
        this.queuedBlurryRecognitionSymbologies = Array.from(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.values());
        this.readyBlurryRecognitionSymbologies = new Set();
        this.preload = preload;
    }
    BlurryRecognitionPreloader.create = function (preload) {
        return tslib_1.__awaiter(this, void 0, void 0, function () {
            var browserName, worker_1;
            return tslib_1.__generator(this, function (_a) {
                if (preload) {
                    browserName = browserHelper_1.BrowserHelper.userAgentInfo.getBrowser().name;
                    if (browserName != null && browserName.includes("Edge")) {
                        worker_1 = new Worker(URL.createObjectURL(new Blob(["(" + BlurryRecognitionPreloader.workerIndexedDBSupportTestFunction.toString() + ")()"], {
                            type: "text/javascript",
                        })));
                        return [2 /*return*/, new Promise(function (resolve) {
                                worker_1.onmessage = function (message) {
                                    worker_1.terminate();
                                    resolve(new BlurryRecognitionPreloader(message.data));
                                };
                            })];
                    }
                }
                return [2 /*return*/, new BlurryRecognitionPreloader(preload)];
            });
        });
    };
    // istanbul ignore next
    BlurryRecognitionPreloader.workerIndexedDBSupportTestFunction = function () {
        try {
            indexedDB.deleteDatabase("scandit_indexeddb_support_test");
            // @ts-ignore
            postMessage(true);
        }
        catch (error) {
            // @ts-ignore
            postMessage(false);
        }
    };
    BlurryRecognitionPreloader.prototype.prepareBlurryTables = function () {
        return tslib_1.__awaiter(this, void 0, void 0, function () {
            var alreadyAvailable, error_1;
            return tslib_1.__generator(this, function (_a) {
                switch (_a.label) {
                    case 0:
                        alreadyAvailable = true;
                        if (!this.preload) return [3 /*break*/, 4];
                        _a.label = 1;
                    case 1:
                        _a.trys.push([1, 3, , 4]);
                        return [4 /*yield*/, this.checkBlurryTablesAlreadyAvailable()];
                    case 2:
                        alreadyAvailable = _a.sent();
                        return [3 /*break*/, 4];
                    case 3:
                        error_1 = _a.sent();
                        // istanbul ignore next
                        console.error(error_1);
                        return [3 /*break*/, 4];
                    case 4:
                        if (alreadyAvailable) {
                            this.queuedBlurryRecognitionSymbologies = [];
                            this.readyBlurryRecognitionSymbologies = new Set(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies);
                            this.eventEmitter.emit("blurryTablesUpdate", new Set(this.readyBlurryRecognitionSymbologies));
                        }
                        else {
                            this.engineWorker = new Worker(URL.createObjectURL(dataCaptureWorker_1.dataCaptureWorkerBlob));
                            this.engineWorker.onmessage = this.engineWorkerOnMessage.bind(this);
                            dataCaptureLoader_1.DataCaptureLoader.load(this.engineWorker, true, true);
                        }
                        return [2 /*return*/];
                }
            });
        });
    };
    BlurryRecognitionPreloader.prototype.on = function (eventName, listener) {
        // istanbul ignore else
        if (eventName === "blurryTablesUpdate") {
            if (this.readyBlurryRecognitionSymbologies.size ===
                BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.size) {
                listener(this.readyBlurryRecognitionSymbologies);
            }
            else {
                this.eventEmitter.on(eventName, listener);
            }
        }
    };
    BlurryRecognitionPreloader.prototype.updateBlurryRecognitionPriority = function (scanSettings) {
        var newQueuedBlurryRecognitionSymbologies = this.queuedBlurryRecognitionSymbologies.slice();
        this.getEnabledSymbologies(scanSettings).forEach(function (symbology) {
            var symbologyQueuePosition = newQueuedBlurryRecognitionSymbologies.indexOf(symbology);
            if (symbologyQueuePosition !== -1) {
                newQueuedBlurryRecognitionSymbologies.unshift(newQueuedBlurryRecognitionSymbologies.splice(symbologyQueuePosition, 1)[0]);
            }
        });
        this.queuedBlurryRecognitionSymbologies = newQueuedBlurryRecognitionSymbologies;
    };
    BlurryRecognitionPreloader.prototype.isBlurryRecognitionAvailable = function (scanSettings) {
        var _this = this;
        var enabledBlurryRecognitionSymbologies = this.getEnabledSymbologies(scanSettings);
        return enabledBlurryRecognitionSymbologies.every(function (symbology) {
            return _this.readyBlurryRecognitionSymbologies.has(symbology);
        });
    };
    BlurryRecognitionPreloader.prototype.getEnabledSymbologies = function (scanSettings) {
        return Array.from(BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.values()).filter(function (symbology) {
            return scanSettings.isSymbologyEnabled(symbology);
        });
    };
    BlurryRecognitionPreloader.prototype.createNextBlurryTableSymbology = function () {
        var symbology;
        do {
            symbology = this.queuedBlurryRecognitionSymbologies.shift();
        } while (symbology != null && this.readyBlurryRecognitionSymbologies.has(symbology));
        // istanbul ignore else
        if (symbology != null) {
            this.engineWorker.postMessage({
                type: "create-blurry-table",
                symbology: symbology,
            });
        }
    };
    BlurryRecognitionPreloader.prototype.checkBlurryTablesAlreadyAvailable = function () {
        return new Promise(function (resolve) {
            var openDbRequest = indexedDB.open(BlurryRecognitionPreloader.writableDataPath);
            function handleErrorOrNew() {
                var _a;
                (_a = openDbRequest === null || openDbRequest === void 0 ? void 0 : openDbRequest.result) === null || _a === void 0 ? void 0 : _a.close();
                // this.error
                resolve(false);
            }
            openDbRequest.onupgradeneeded = function () {
                try {
                    openDbRequest.result.createObjectStore(BlurryRecognitionPreloader.fsObjectStoreName);
                }
                catch (error) {
                    // Ignored
                }
            };
            openDbRequest.onsuccess = function () {
                try {
                    var transaction = openDbRequest.result.transaction(BlurryRecognitionPreloader.fsObjectStoreName, "readonly");
                    transaction.onerror = handleErrorOrNew;
                    var storeKeysRequest_1 = transaction
                        .objectStore(BlurryRecognitionPreloader.fsObjectStoreName)
                        .getAllKeys();
                    storeKeysRequest_1.onsuccess = function () {
                        openDbRequest.result.close();
                        if ((__1.highEndBlurryRecognition
                            ? BlurryRecognitionPreloader.highEndBlurryTableFiles
                            : BlurryRecognitionPreloader.defaultBlurryTableFiles).every(function (file) {
                            return storeKeysRequest_1.result.indexOf(file) !== -1;
                        })) {
                            return resolve(true);
                        }
                        else {
                            return resolve(false);
                        }
                    };
                    storeKeysRequest_1.onerror = handleErrorOrNew;
                }
                catch (error) {
                    handleErrorOrNew.call({ error: error });
                }
            };
            openDbRequest.onblocked = openDbRequest.onerror = handleErrorOrNew;
        });
    };
    BlurryRecognitionPreloader.prototype.engineWorkerOnMessage = function (ev) {
        var _this = this;
        var data = ev.data;
        // istanbul ignore else
        if (data[1] != null) {
            switch (data[0]) {
                case "context-created":
                    this.createNextBlurryTableSymbology();
                    break;
                case "create-blurry-table-result":
                    this.readyBlurryRecognitionSymbologies.add(data[1]);
                    if ([barcode_1.Barcode.Symbology.EAN8, barcode_1.Barcode.Symbology.EAN13, barcode_1.Barcode.Symbology.UPCA, barcode_1.Barcode.Symbology.UPCE].includes(data[1])) {
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.EAN13);
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.EAN8);
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.UPCA);
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.UPCE);
                    }
                    else if ([barcode_1.Barcode.Symbology.CODE32, barcode_1.Barcode.Symbology.CODE39].includes(data[1])) {
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.CODE32);
                        this.readyBlurryRecognitionSymbologies.add(barcode_1.Barcode.Symbology.CODE39);
                    }
                    this.eventEmitter.emit("blurryTablesUpdate", new Set(this.readyBlurryRecognitionSymbologies));
                    if (this.readyBlurryRecognitionSymbologies.size ===
                        BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies.size) {
                        // Avoid data not being persisted if IndexedDB operations in WebWorker are slow
                        setTimeout(function () {
                            _this.engineWorker.terminate();
                        }, 250);
                    }
                    else {
                        this.createNextBlurryTableSymbology();
                    }
                    break;
                // istanbul ignore next
                default:
                    break;
            }
        }
    };
    BlurryRecognitionPreloader.writableDataPath = "/scandit_sync_folder_preload";
    BlurryRecognitionPreloader.fsObjectStoreName = "FILE_DATA";
    // From AndroidLowEnd
    BlurryRecognitionPreloader.defaultBlurryTableFiles = [
        "/281f654b8ff82daa99ad885ef39a15fb.scandit",
        "/2c53cab9a0737960a56ec66ae2a1c2cd.scandit",
        "/6b5c52b06ec25af4ac80a807f08c8a22.scandit",
        "/a161ee7d1b0a5c1f3cf6fbdf41d544da.scandit",
        "/c22ac7d324d8076de6c6a20667cb58fb.scandit",
        "/db921bb2d0f06e25180139366579b318.scandit",
        "/53f7125006c6641b34eed19c3863e42a.scandit",
        "/6de91450426ad609398ffc0dd417066c.scandit",
        "/de892fb0f0b231aa877beb05ef628982.scandit",
        "/27efecb40cc701f1568a100081473470.scandit",
        "/37c247f983a341588eca92f4095982f6.scandit",
        "/9fa42646d1b7ab87f5dbc66c7423275e.scandit",
        "/423b33a061cea7c3e9a346761064e696.scandit",
        "/47fe40b164917635e99f9d917ea873df.scandit",
        "/c86520b1e03d20ad23c7aa3057bc00aa.scandit",
        "/8ca1870a78346f894973385bac861368.scandit",
        "/d6bc81e9953262efe2ba28dc88a255c7.scandit",
        "/f2cc6637d1f431587ae8f0050944b1f6.scandit",
        "/3a749978f5d673142bdcb360f7f6f943.scandit",
        "/5794f5949d313c1c3b8d0ad8235352a4.scandit",
        "/9a5f9ee72580f702ea388b0b2b29ad06.scandit", // msi-plessey
    ].map(function (path) {
        return "" + BlurryRecognitionPreloader.writableDataPath + path;
    });
    // From AndroidGeneric
    BlurryRecognitionPreloader.highEndBlurryTableFiles = [
        "/190321966be83d9d4eb3ebef42e0425c.scandit",
        "/51e855045b2f56ecc18e92b1c53c302c.scandit",
        "/5e2464c47c50ac324766b4f7836a9238.scandit",
        "/7f95c7a85f7644081420026f011afc26.scandit",
        "/9768cd567a0813ef9e2b35377e5763b3.scandit",
        "/9da839200be5f945ae07ce56be4b519b.scandit",
        "/1e09ddd31d6b791f2aff1fc178fc0fa6.scandit",
        "/4e6cfc8f10105c1c88be188781e1fd09.scandit",
        "/777cff34a643cc67783abc5a2cd28028.scandit",
        "/7a47da9075339736d97d20e74743adb4.scandit",
        "/83b2f2f20564df0c4c3343abdd33ce2c.scandit",
        "/876aa038cde59f3bc554408ef6de5aba.scandit",
        "/525eb9a51a6d7a247a718bd47e8e6fca.scandit",
        "/5c72db14fd540dd7ed0a1a8e03d1a08d.scandit",
        "/61014b41bd1a00c842a881267d5b47bf.scandit",
        "/748fd6c978b0f7e02fa4c5f481f69a92.scandit",
        "/7db7b21c46a607367ee9993279d4bf06.scandit",
        "/b5189294cd7b8c5428008b37a4ebee57.scandit",
        "/28307ba88850bdbf0ca3c02bc00ce76c.scandit",
        "/2f239cbc1915384192586bb52f1e20d5.scandit",
        "/443c732a519cd45ae3de1b90eca2221a.scandit",
        "/4a7685d7441e9ed9b08342273033d654.scandit",
        "/5d777eae7a2b98a13183dbab6ab05f87.scandit",
        "/bfdd27616e9e53ec1256e61025c87e4f.scandit",
        "/0cf46df76c8afda2dd17eada4c0aa3d9.scandit",
        "/55c134f1aa08ae47b6f1101b03ff1369.scandit",
        "/7b5c8ef98b4497fe700a3647dcccc4e6.scandit",
        "/8d97762fcf3c987deeca8e790b124273.scandit",
        "/b2881842e74d4b75fa0dcbb2658f0da3.scandit",
        "/fc5e2552d2904a71a912dacaa0547efe.scandit",
        "/00918cc9b4ad74bf76111e9fa70e158e.scandit",
        "/4f10a1584fa6bfa1af2bfc95f938d192.scandit",
        "/61579472d3ab4998bfcc9e3070f39354.scandit",
        "/a5b8d6eee7ccd778f4b42d840add2539.scandit",
        "/ce62d7332b17011763bd79516d908235.scandit",
        "/ed70de938d43e92a43f5176f0fb3aef0.scandit",
        "/2dc97c75a0fafc59e91c76f766b8372d.scandit",
        "/64a3982f73cd8050fdb4b1a6e8c07537.scandit",
        "/6eb7c32c9bc81edaec9e816615538484.scandit",
        "/866c3631e1963d133c8598b60675894d.scandit",
        "/ce6c0d7ebc0081eeeb51c82beddba8a7.scandit",
        "/ffd07d94597bc9622936112d5cbacbbe.scandit", // msi-plessey
    ].map(function (path) {
        return "" + BlurryRecognitionPreloader.writableDataPath + path;
    });
    // Roughly ordered by priority
    BlurryRecognitionPreloader.availableBlurryRecognitionSymbologies = new Set([
        barcode_1.Barcode.Symbology.EAN13,
        barcode_1.Barcode.Symbology.EAN8,
        barcode_1.Barcode.Symbology.CODE32,
        barcode_1.Barcode.Symbology.CODE39,
        barcode_1.Barcode.Symbology.CODE128,
        barcode_1.Barcode.Symbology.CODE93,
        barcode_1.Barcode.Symbology.INTERLEAVED_2_OF_5,
        barcode_1.Barcode.Symbology.MSI_PLESSEY,
        barcode_1.Barcode.Symbology.CODABAR,
        barcode_1.Barcode.Symbology.UPCA,
        barcode_1.Barcode.Symbology.UPCE, // Shared with EAN8, EAN13, UPCA
    ]);
    return BlurryRecognitionPreloader;
}());
exports.BlurryRecognitionPreloader = BlurryRecognitionPreloader;
//# sourceMappingURL=blurryRecognitionPreloader.js.map