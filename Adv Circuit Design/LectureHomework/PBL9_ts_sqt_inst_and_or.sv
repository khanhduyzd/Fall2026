
`timescale 1ns/1ps

module tb_seq_inst_and_or;

    reg a, b, c, d, e, f, g;
    reg clk, rst;
    wire n;

    reg expected_j, expected_k;
    reg expected_n, expected_l;
    reg next_j, next_k;

    integer i, errors;

    seq_inst_and_or uut (
        .a(a), .b(b), .c(c), .d(d),
        .e(e), .f(f), .g(g),
        .clk(clk), .rst(rst),
        .n(n)
    );

    // Clock generator
    initial begin
        clk = 0;
        forever #5 clk = ~clk;
    end

    initial begin
        {a, b, c, d, e, f, g} = 0;
        rst = 0;
        errors = 0;

        expected_j = 0;
        expected_k = 0;
        expected_n = 0;

        #2;

        if (n !== 1'b0) begin
            $display("ERROR: Reset failed");
            errors = errors + 1;
        end

        #10;
        rst = 1;

        // Test all 128 combinations
        for (i = 0; i < 128; i = i + 1) begin

            @(negedge clk);

            {a, b, c, d, e, f, g} = i;

            next_j = (a & b) | c | d;
            next_k = a & c & d;

            expected_l = (expected_k & e) | f | g;
            expected_n = expected_j ^ expected_l;

            @(posedge clk);
            #1;

            if (n !== expected_n) begin
                $display("ERROR: input=%b%b%b%b%b%b%b n=%b expected=%b",
                         a, b, c, d, e, f, g,
                         n, expected_n);
                errors = errors + 1;
            end

            expected_j = next_j;
            expected_k = next_k;
        end

        if (errors == 0)
            $display("PASS: All 128 combinations passed.");
        else
            $display("FAIL: %0d errors.", errors);

        $finish;
    end

endmodule

